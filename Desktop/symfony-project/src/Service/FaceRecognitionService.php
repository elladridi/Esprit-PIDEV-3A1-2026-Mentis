<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FaceRecognitionService
{
    private string $facesDir;
    private string $scriptPath;
    private string $cascadePath;
    private string $pythonBin;

    private const REQUIRED_SAMPLES    = 3;
    private const QUALITY_THRESHOLD   = 10.0;
    private const SIMILARITY_THRESHOLD = 30.0;

    public function __construct(
        string $projectDir,
        private EntityManagerInterface $em,
        private UserRepository $userRepo
    ) {
        $this->facesDir    = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'faces' . DIRECTORY_SEPARATOR;
        $this->scriptPath  = $projectDir . DIRECTORY_SEPARATOR . 'scripts'  . DIRECTORY_SEPARATOR . 'face.py';
        $this->cascadePath = $projectDir . DIRECTORY_SEPARATOR . 'public'   . DIRECTORY_SEPARATOR . 'haarcascade_frontalface_default.xml';
        $this->pythonBin   = 'C:\\Users\\DELL\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe';

        if (!is_dir($this->facesDir)) {
            mkdir($this->facesDir, 0755, true);
        }
    }

    public function registerSample(int $userId, UploadedFile $imageFile): array
    {
        $user = $this->userRepo->find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // 1. Create user folder
        $userDir = $this->facesDir . $userId . DIRECTORY_SEPARATOR;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        // 2. Save temp file into user folder
        $tempName = 'tmp_' . uniqid() . '.jpg';
        $imageFile->move($userDir, $tempName);
        $tempPath = $userDir . $tempName;

        // 3. Detect face
        $detection = $this->runDetect($tempPath);
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        if (!($detection['face_detected'] ?? false)) {
            $processedPath = str_replace('.jpg', '_face.jpg', $tempPath);
            if (file_exists($processedPath)) {
                @unlink($processedPath);
            }
            return [
                'success' => false,
                'message' => $detection['error'] ?? 'No face detected — check lighting.'
            ];
        }

        // 4. Quality check
        if (($detection['quality'] ?? 0) < self::QUALITY_THRESHOLD) {
            $processedPath = $detection['processed_path'] ?? null;
            if ($processedPath && file_exists($processedPath)) {
                @unlink($processedPath);
            }
            return [
                'success' => false,
                'message' => 'Image too blurry (quality: ' . round($detection['quality'], 1) . ').'
            ];
        }

        // 5. Move processed crop to final location
        $processedPath = $detection['processed_path'];
        if (!file_exists($processedPath)) {
            return ['success' => false, 'message' => 'Processed face not found.'];
        }

        $existingSamples = $this->getUserSamples($userId);
        $sampleIndex     = count($existingSamples);
        $finalPath       = $userDir . 'sample_' . $sampleIndex . '.jpg';

        if (!rename($processedPath, $finalPath)) {
            return ['success' => false, 'message' => 'Failed to save sample file.'];
        }

        // 6. ✅ Save path to database via User entity
        $relativePath = 'uploads/faces/' . $userId . '/sample_' . $sampleIndex . '.jpg';
        $user->addFaceSample($relativePath);

        $sampleCount = $sampleIndex + 1;
        $completed   = $sampleCount >= self::REQUIRED_SAMPLES;

        if ($completed) {
            $user->enableFaceRecognition(); // sets faceEnabled=true + faceRegisteredAt
        }

        $this->em->flush(); // ✅ Persist to DB

        return [
            'success'      => true,
            'sample_count' => $sampleCount,
            'remaining'    => max(0, self::REQUIRED_SAMPLES - $sampleCount),
            'completed'    => $completed,
            'quality'      => round($detection['quality'], 1),
            'message'      => $completed
                ? 'Face ID registered successfully!'
                : 'Sample ' . $sampleCount . ' saved. ' . (self::REQUIRED_SAMPLES - $sampleCount) . ' more needed.'
        ];
    }

    public function verifyAgainstAll(array $users, UploadedFile $imageFile): array
    {
        $tempName = 'ver_' . uniqid() . '.jpg';
        $imageFile->move($this->facesDir, $tempName);
        $tempPath = $this->facesDir . $tempName;

        $detection = $this->runDetect($tempPath);
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        if (!($detection['face_detected'] ?? false)) {
            return ['success' => false, 'message' => 'No face detected in photo.'];
        }

        $verifyFacePath = $detection['processed_path'];
        $bestScore      = 0.0;
        $bestUser       = null;

        foreach ($users as $user) {
            $samples = $this->getUserSamples($user->getId());
            foreach ($samples as $samplePath) {
                $result = $this->runCompare($verifyFacePath, $samplePath);
                $score  = (float)($result['similarity'] ?? 0);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestUser  = $user;
                }
            }
        }

        if (file_exists($verifyFacePath)) {
            @unlink($verifyFacePath);
        }

        $matched = ($bestScore >= self::SIMILARITY_THRESHOLD) && ($bestUser !== null);

        return [
            'success'    => $matched,
            'user'       => $matched ? $bestUser : null,
            'confidence' => round($bestScore, 2),
            'message'    => $matched
                ? 'Face matched'
                : sprintf('No match — best score %.1f%% (need %.0f%%)', $bestScore, self::SIMILARITY_THRESHOLD)
        ];
    }

    public function deleteUserFaces(int $userId): void
    {
        $userDir = $this->facesDir . $userId . DIRECTORY_SEPARATOR;
        if (is_dir($userDir)) {
            foreach (glob($userDir . '*.jpg') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($userDir);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function runDetect(string $imagePath): array
    {
        $cmd = sprintf(
            '"%s" "%s" detect "%s" "%s" 2>&1',
            $this->pythonBin,
            $this->scriptPath,
            $imagePath,
            $this->cascadePath
        );

        $output = shell_exec($cmd);
        if (!$output) {
            return ['face_detected' => false, 'error' => 'Python script returned no output'];
        }

        $lines  = array_filter(array_map('trim', explode("\n", $output)));
        $result = json_decode(end($lines), true);

        return (json_last_error() === JSON_ERROR_NONE)
            ? $result
            : ['face_detected' => false, 'error' => 'Script output: ' . $output];
    }

    private function runCompare(string $image1, string $image2): array
    {
        $cmd = sprintf(
            '"%s" "%s" compare "%s" "%s" 2>&1',
            $this->pythonBin,
            $this->scriptPath,
            $image1,
            $image2
        );

        $output = shell_exec($cmd);
        if (!$output) {
            return ['similarity' => 0];
        }

        $lines  = array_filter(array_map('trim', explode("\n", $output)));
        $result = json_decode(end($lines), true);

        return (json_last_error() === JSON_ERROR_NONE) ? $result : ['similarity' => 0];
    }

    private function getUserSamples(int $userId): array
    {
        $userDir = $this->facesDir . $userId . DIRECTORY_SEPARATOR;
        if (!is_dir($userDir)) {
            return [];
        }
        return glob($userDir . 'sample_*.jpg') ?: [];
    }
}