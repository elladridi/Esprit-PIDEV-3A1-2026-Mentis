<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Smalot\PdfParser\Parser;
use App\Service\FaceRecognitionService;
use App\Service\CVSummarizationService;

class RegistrationController extends AbstractController
{
    private const FACES_DIR = 'uploads/faces/';
    private const REQUIRED_SAMPLES = 1;

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── STEP 1: Hash password FIRST ───────────────────────────────
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // ── STEP 2: CV analysis for psychologists ─────────────────────
            $cvFile = $form->get('cvFile')->getData();

            if ($cvFile && $user->getType() === 'Psychologist') {
                try {
                    $tempDir = $this->getParameter('kernel.project_dir') . '/var/temp_cvs/';
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0777, true);
                    }

                    $tempFileName = uniqid() . '.pdf';
                    $cvFile->move($tempDir, $tempFileName);
                    $tempFilePath = $tempDir . $tempFileName;

                    $cvText  = $this->extractTextFromPDF($tempFilePath);
                    $analysis = $this->analyzeCVWithZai($cvText);

                    @unlink($tempFilePath);

                    if (!empty($analysis['firstname']) && empty($user->getFirstname())) {
                        $user->setFirstname($analysis['firstname']);
                    }
                    if (!empty($analysis['lastname']) && empty($user->getLastname())) {
                        $user->setLastname($analysis['lastname']);
                    }
                    if (!empty($analysis['email']) && empty($user->getEmail())) {
                        $user->setEmail($analysis['email']);
                    }
                    if (!empty($analysis['phone']) && empty($user->getPhone())) {
                        $user->setPhone($analysis['phone']);
                    }
                    if (!empty($analysis['dateofbirth']) && empty($user->getDateofbirth())) {
                        $user->setDateofbirth($analysis['dateofbirth']);
                    }
                    if (!empty($analysis['gender']) && empty($user->getGender())) {
                        $user->setGender($analysis['gender']);
                    }

                    $session = $request->getSession();
                    $session->set('cv_analysis_' . $user->getEmail(), [
                        'firstname'            => $analysis['firstname'],
                        'lastname'             => $analysis['lastname'],
                        'email'                => $analysis['email'],
                        'phone'                => $analysis['phone'],
                        'dateofbirth'          => $analysis['dateofbirth'],
                        'gender'               => $analysis['gender'],
                        'score'                => $analysis['score'] ?? 0,
                        'is_valid'             => $analysis['is_valid'] ?? false,
                        'degree_found'         => $analysis['degree_found'] ?? false,
                        'experience_years'     => $analysis['experience_years'] ?? 0,
                        'specializations'      => $analysis['specializations'] ?? [],
                        'missing_requirements' => $analysis['missing_requirements'] ?? [],
                        'analysis_date'        => date('Y-m-d H:i:s'),
                    ]);

                    $this->addFlash('info',
                        '📄 CV Analysis: ' .
                        'Name: ' . ($analysis['firstname'] ?: 'Not found') . ' ' . ($analysis['lastname'] ?: '') . ', ' .
                        'Email: ' . ($analysis['email'] ?: 'Not found') . ', ' .
                        'Phone: ' . ($analysis['phone'] ?: 'Not found') . ', ' .
                        'Experience: ' . $analysis['experience_years'] . ' years, ' .
                        'Score: ' . $analysis['score'] . '%'
                    );

                    if ($analysis['is_valid'] ?? false) {
                        $this->addFlash('success', '✓ CV validated! Welcome to Mentis.');
                    } else {
                        $this->addFlash('warning', '⚠️ Your CV is under review. Missing: ' . implode(', ', $analysis['missing_requirements']));
                    }

                } catch (\Exception $e) {
                    $this->addFlash('error', 'CV analysis failed: ' . $e->getMessage());
                }
            }

            // ── STEP 3: Persist user (need ID before face processing) ─────
            $entityManager->persist($user);
            $entityManager->flush();

            // ── STEP 4: Face ID (optional) ────────────────────────────────
            $faceSamplesJson = $request->request->get('face_samples');

            if ($faceSamplesJson && !empty($faceSamplesJson)) {
                $samples = json_decode($faceSamplesJson, true);

                if (is_array($samples) && count($samples) >= 3) {

                    $projectDir = $this->getParameter('kernel.project_dir');
                    $facesDir   = $projectDir . DIRECTORY_SEPARATOR . 'public'
                                . DIRECTORY_SEPARATOR . 'uploads'
                                . DIRECTORY_SEPARATOR . 'faces'
                                . DIRECTORY_SEPARATOR;

                    $userDir = $facesDir . $user->getId() . DIRECTORY_SEPARATOR;
                    if (!is_dir($userDir)) {
                        mkdir($userDir, 0755, true);
                    }

                    $savedCount = 0;
                    foreach ($samples as $sampleData) {
                        if (str_contains($sampleData, 'base64,')) {
                            $sampleData = explode('base64,', $sampleData)[1];
                        }

                        $imageData = base64_decode($sampleData);
                        if (!$imageData || strlen($imageData) < 1000) continue;

                        $tempName = 'reg_tmp_' . uniqid() . '.jpg';
                        $tempPath = $facesDir . $tempName;
                        file_put_contents($tempPath, $imageData);

                        $scriptPath  = $projectDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'face.py';
                        $cascadePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'haarcascade_frontalface_default.xml';
                        $python      = 'C:\\Users\\DELL\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe';

                        $cmd    = sprintf('"%s" "%s" detect "%s" "%s" 2>&1', $python, $scriptPath, $tempPath, $cascadePath);
                        $output = shell_exec($cmd);
                        @unlink($tempPath);

                        if (!$output) continue;

                        $lines     = array_filter(array_map('trim', explode("\n", $output)));
                        $detection = json_decode(end($lines), true);

                        if (!($detection['face_detected'] ?? false)) continue;

                        $processedPath = $detection['processed_path'] ?? '';
                        if (!file_exists($processedPath)) continue;

                        rename($processedPath, $userDir . 'sample_' . $savedCount . '.jpg');
                        $savedCount++;
                    }

                    if ($savedCount >= 3) {
                        $user->setFaceEnabled(true);
                        if (method_exists($user, 'setFaceRegisteredAt')) {
                            $user->setFaceRegisteredAt(new \DateTime());
                        }
                        $entityManager->flush();
                        $this->addFlash('success', '✓ Face ID registered successfully!');
                    } else {
                        $this->addFlash('warning', "Only $savedCount face samples were valid. Face ID not enabled.");
                    }
                }
            }

            // ── STEP 5: Always redirect from one place ────────────────────
            $this->addFlash('success', 'Registration successful! Please login.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/analyze-cv', name: 'app_analyze_cv', methods: ['POST'])]
    public function analyzeCv(Request $request, CVSummarizationService $cvService): JsonResponse
    {
        $cvFile = $request->files->get('cv');

        if (!$cvFile) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        try {
            $tempDir = $this->getParameter('kernel.project_dir') . '/var/temp_cvs/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $tempFileName = uniqid() . '.pdf';
            $cvFile->move($tempDir, $tempFileName);
            $tempFilePath = $tempDir . $tempFileName;

            $cvText  = $this->extractTextFromPDF($tempFilePath);
            $analysis = $this->analyzeCVWithZai($cvText);

            @unlink($tempFilePath);

            return $this->json([
                'success'     => true,
                'firstname'   => $analysis['firstname'],
                'lastname'    => $analysis['lastname'],
                'email'       => $analysis['email'],
                'phone'       => $analysis['phone'],
                'dateofbirth' => $analysis['dateofbirth'],
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function extractTextFromPDF(string $pdfPath): string
    {
        // Strategy 1: smalot/pdf-parser (preserves line breaks — important for name extraction)
        if (class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new Parser();
                $pdf    = $parser->parseFile($pdfPath);
                $pages  = $pdf->getPages();
                $text   = '';
                foreach ($pages as $page) {
                    $text .= $page->getText() . "\n";
                }
                $text = trim($text);
                if ($text && strlen($text) > 50) {
                    return substr($text, 0, 5000);
                }
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Strategy 2: pdftotext CLI (best quality, preserves layout)
        if (function_exists('shell_exec')) {
            $commands = [
                'pdftotext -layout ' . escapeshellarg($pdfPath) . ' - 2>/dev/null',
                '"C:\\Program Files\\poppler\\bin\\pdftotext.exe" -layout ' . escapeshellarg($pdfPath) . ' - 2>nul',
            ];
            foreach ($commands as $command) {
                $output = shell_exec($command);
                if ($output && strlen($output) > 100 && !str_contains($output, 'ERROR')) {
                    return substr($output, 0, 5000);
                }
            }
        }

        // Strategy 3: raw PDF string extraction (last resort)
        $content = file_get_contents($pdfPath);
        if ($content) {
            preg_match_all('/\(([^)]+)\)/', $content, $matches);
            if (!empty($matches[1])) {
                $text = implode("\n", $matches[1]);
                $text = preg_replace('/[^\x20-\x7E\n]/', ' ', $text);
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                if (strlen($text) > 100) {
                    return substr($text, 0, 5000);
                }
            }
        }

        return '';
    }

    private function analyzeCVWithZai(string $cvText): array
    {
        // ── CLEAN: strip PDF font garbage (cid:xxx) injected by smalot ───
        $text      = preg_replace('/\(cid:\d+\)\s*/i', '', $cvText);
        $lines     = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $textLower = mb_strtolower($text);

        // ── EMAIL ─────────────────────────────────────────────────────────
        $email    = '';
        $emailPat = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';

        // 1. Labeled: "Email: x@y.com"
        if (preg_match('/(?:email|e-mail|courriel)\s*[:\s]\s*(' . substr($emailPat, 1, -1) . ')/i', $text, $m)) {
            $email = $m[1];
        }
        // 2. Line-by-line, split on | , ; — strip spaces per part (fixes smalot corruption)
        if (empty($email)) {
            foreach ($lines as $line) {
                foreach (preg_split('/[|,;]/', $line) as $part) {
                    $partNoSpace = preg_replace('/\s+/', '', ltrim($part, '# '));
                    if (preg_match($emailPat, $partNoSpace, $m)) {
                        $candidate = $m[0];
                        if (strpos($candidate, '..') === false && strlen($candidate) < 80) {
                            $email = $candidate;
                            break 2;
                        }
                    }
                }
            }
        }
        // 3. Bare fallback
        if (empty($email) && preg_match($emailPat, $text, $m)) {
            $email = $m[0];
        }

        // ── PHONE ─────────────────────────────────────────────────────────
        $phone = '';
        if (preg_match('/\+\d[\d\s\-\.()]{6,18}\d/', $text, $m)) {
            $phone = trim(preg_replace('/\s+/', ' ', $m[0]));
        }
        if (empty($phone) && preg_match('/(?:tel|phone|t[eé]l|mobile|gsm)\s*[:\s]\s*([\d\s\-\.()]{8,20})/i', $text, $m)) {
            $phone = trim($m[1]);
        }
        if (empty($phone) && preg_match('/\b(\d{8,15})\b/', $text, $m)) {
            $phone = $m[1];
        }

        // ── NAME ──────────────────────────────────────────────────────────
        $firstName = '';
        $lastName  = '';

        $skipWords = [
            'curriculum','vitae','resume','cv','profile','profil','summary',
            'about','contact','education','formation','experience','skills',
            'competence','objective','languages','projects','certifications',
            'references','interests','awards','achievements','langues',
            'projets','parcours',
        ];

        $onlyAlphaHyphen = function (string $s): bool {
            foreach (mb_str_split($s) as $c) {
                if (!ctype_alpha($c) && $c !== ' ' && $c !== '-') return false;
            }
            return true;
        };

        foreach (array_slice($lines, 0, 8) as $line) {
            $cl    = trim(preg_replace('/^(dr\.?|mr\.?|mrs\.?|ms\.?|prof\.?)\s+/i', '', $line));
            $words = preg_split('/\s+/', $cl);

            if (count($words) < 2 || count($words) > 4) continue;
            if (preg_match('/\d/', $cl)) continue;
            if (str_contains($cl, '@') || str_contains(strtolower($cl), 'http')) continue;

            $hasSkip = false;
            foreach ($words as $w) {
                if (in_array(rtrim(strtolower($w), 's'), $skipWords)) { $hasSkip = true; break; }
            }
            if ($hasSkip) continue;
            if (!$onlyAlphaHyphen($cl)) continue;

            // ALL CAPS
            if ($cl === mb_strtoupper($cl)) {
                $firstName = mb_convert_case($words[0], MB_CASE_TITLE, 'UTF-8');
                $lastName  = implode(' ', array_map(fn($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), array_slice($words, 1)));
                break;
            }
            // Title Case
            if (mb_strtoupper($cl[0]) === $cl[0]) {
                $firstName = $words[0];
                $lastName  = implode(' ', array_slice($words, 1));
                break;
            }
            // lowercase
            if ($cl === mb_strtolower($cl)) {
                $firstName = mb_convert_case($words[0], MB_CASE_TITLE, 'UTF-8');
                $lastName  = implode(' ', array_map(fn($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), array_slice($words, 1)));
                break;
            }
        }

        // Labeled fallback: "Name: First Last"
        if (empty($firstName)) {
            if (preg_match('/(?:name|full\s*name|nom(?:\s*complet)?)\s*[:\s]\s*(\S+)\s+(\S+)/i', $text, $m)) {
                if ($onlyAlphaHyphen($m[1] . $m[2])) {
                    $firstName = mb_convert_case($m[1], MB_CASE_TITLE, 'UTF-8');
                    $lastName  = mb_convert_case($m[2], MB_CASE_TITLE, 'UTF-8');
                }
            }
        }

        // ── DATE OF BIRTH ─────────────────────────────────────────────────
        $dateOfBirth = '';
        $dobPatterns = [
            '/(?:born|birth|dob|n[ée]\s*le|date\s*of\s*birth)\s*[:\s]\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            '/(?:born|birth|dob|n[ée]\s*le|date\s*of\s*birth)\s*[:\s]\s*(\d{4}[\/\-]\d{2}[\/\-]\d{2})/i',
            '/\b(\d{4}-\d{2}-\d{2})\b/',
            '/\b(\d{2}[\/\-]\d{2}[\/\-]\d{4})\b/',
        ];
        foreach ($dobPatterns as $pat) {
            if (preg_match($pat, $text, $m)) {
                $candidate = $m[1];
                if (preg_match('/^(\d{4})/', $candidate, $yr)) {
                    $year = (int)$yr[1];
                    if ($year < 1940 || $year > 2010) continue;
                }
                $dateOfBirth = str_replace(['.', '/'], '-', $candidate);
                break;
            }
        }

        // ── GENDER ────────────────────────────────────────────────────────
        $gender    = '';
        $genderMap = [
            'female' => 'female', 'femme' => 'female', 'féminin' => 'female',
            ' male ' => 'male', 'masculin' => 'male', ' homme ' => 'male',
        ];
        foreach ($genderMap as $keyword => $value) {
            if (str_contains($textLower, $keyword)) { $gender = $value; break; }
        }

        // ── PSYCHOLOGY VALIDATION ─────────────────────────────────────────
        $degreeKeywords = ['master', 'phd', 'doctorate', 'doctorat', "master's", 'masters', 'maîtrise', 'doctor'];
        $degreeFound    = false;
        foreach ($degreeKeywords as $kw) {
            if (str_contains($textLower, $kw)) { $degreeFound = true; break; }
        }

        $experienceYears = 0;
        if (preg_match('/(\d+)\s*(?:years?|ans?\b)/i', $textLower, $m)) {
            $experienceYears = (int)$m[1];
        }

        $specKeywords    = ['cbt' => 'CBT', 'clinical' => 'Clinical', 'therapy' => 'Therapy'];
        $specializations = [];
        foreach ($specKeywords as $kw => $label) {
            if (str_contains($textLower, $kw)) $specializations[] = $label;
        }
        $specializations = array_unique($specializations);

        $minExp  = 2;
        $isValid = $degreeFound && ($experienceYears >= $minExp);
        $score   = min(
            ($degreeFound ? 50 : 0) + min($experienceYears * 10, 40) + min(count($specializations) * 5, 10),
            100
        );

        $missing = [];
        if (!$degreeFound)              $missing[] = 'Master or PhD degree in Psychology';
        if ($experienceYears < $minExp) $missing[] = "Minimum {$minExp} years experience (found: {$experienceYears})";

        return [
            'firstname'            => $firstName,
            'lastname'             => $lastName,
            'email'                => $email,
            'phone'                => $phone,
            'dateofbirth'          => $dateOfBirth,
            'gender'               => $gender,
            'degree_found'         => $degreeFound,
            'experience_years'     => $experienceYears,
            'specializations'      => $specializations,
            'is_valid'             => $isValid,
            'score'                => $score,
            'missing_requirements' => $missing,
        ];
    }
}