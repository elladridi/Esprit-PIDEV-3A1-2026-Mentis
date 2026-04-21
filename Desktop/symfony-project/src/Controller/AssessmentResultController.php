<?php
// src/Controller/AssessmentResultController.php

namespace App\Controller;

use App\Entity\AssessmentResult;
use App\Entity\User;
use App\Service\CrisisAlertService;
use App\Repository\AssessmentRepository;
use App\Repository\AssessmentResultRepository;
use App\Repository\QuestionRepository;
use App\Repository\UserRepository;
use App\Service\GroqService;
use App\Service\YouTubeService;
use App\Service\PdfExportService;
use App\Service\SpotifyService;
use App\Service\MeditationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/result')]
class AssessmentResultController extends AbstractController
{
    private EntityManagerInterface $em;
    private AssessmentResultRepository $resultRepo;
    private AssessmentRepository $assessmentRepo;
    private QuestionRepository $questionRepo;
    private GroqService $groqService;
    private YouTubeService $youtubeService;
    private PdfExportService $pdfExportService;
    private SpotifyService $spotifyService;
    private MeditationService $meditationService;
    private CrisisAlertService $crisisAlertService;

    public function __construct(
        EntityManagerInterface $em,
        AssessmentResultRepository $resultRepo,
        AssessmentRepository $assessmentRepo,
        QuestionRepository $questionRepo,
        GroqService $groqService,
        YouTubeService $youtubeService,
        PdfExportService $pdfExportService,
        SpotifyService $spotifyService,
        MeditationService $meditationService,
        CrisisAlertService $crisisAlertService
    ) {
        $this->em = $em;
        $this->resultRepo = $resultRepo;
        $this->assessmentRepo = $assessmentRepo;
        $this->questionRepo = $questionRepo;
        $this->groqService = $groqService;
        $this->youtubeService = $youtubeService;
        $this->pdfExportService = $pdfExportService;
        $this->spotifyService = $spotifyService;
        $this->meditationService = $meditationService;
        $this->crisisAlertService = $crisisAlertService;
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user;
        }
        return null;
    }

    private function isPatient(): bool
    {
        $user = $this->getCurrentUser();
        return $user !== null && $user->getType() === 'Patient';
    }

    private function resultBelongsToCurrentUser(AssessmentResult $result): bool
    {
        $user = $this->getCurrentUser();
        if ($user === null) return false;
        return $result->getUser()?->getId() === $user->getId();
    }

    #[Route('/', name: 'result_index', methods: ['GET'])]
    public function index(): Response
    {
        $currentUser = $this->getCurrentUser();

        if ($this->isPatient() && $currentUser !== null) {
            $results = $this->resultRepo->findByUser($currentUser->getId());
        } else {
            $results = $this->resultRepo->findAllOrderedByDate();
        }

        $users = [];
        foreach ($results as $result) {
            $user = $result->getUser();
            if ($user && !isset($users[$user->getId()])) {
                $users[$user->getId()] = $user;
            }
        }

        return $this->render('result/index.html.twig', [
            'results' => $results,
            'users'   => $users,
        ]);
    }

    #[Route('/user/{userId}', name: 'result_by_user', methods: ['GET'])]
    public function byUser(int $userId, UserRepository $userRepo): Response
    {
        $currentUser = $this->getCurrentUser();

        if ($this->isPatient() && $currentUser !== null
            && $currentUser->getId() !== $userId) {

            $this->addFlash('error', 'You can only view your own results.');
            return $this->redirectToRoute('result_index');
        }

        $results = $this->resultRepo->findByUser($userId);
        $user    = $userRepo->find($userId);

        $users = [];
        if ($user) {
            $users[$userId] = $user;
        }

        return $this->render('result/index.html.twig', [
            'results' => $results,
            'users'   => $users,
            'userId'  => $userId,
        ]);
    }

    #[Route('/submit', name: 'result_submit', methods: ['POST'])]
    public function submit(Request $request, UserRepository $userRepo): Response
    {
        $userId       = $request->request->get('user_id');
        $assessmentId = $request->request->get('assessment_id');
        $answers      = $request->request->all('answers');
        $freeText     = trim($request->request->get('free_text', ''));

        $assessment = $this->assessmentRepo->find($assessmentId);
        if (!$assessment) {
            $this->addFlash('error', 'Assessment not found');
            return $this->redirectToRoute('take_assessment_index');
        }

        $questions = $this->questionRepo->findByAssessment($assessmentId);

        $scores          = [];
        $originalAnswers = [];
        $totalScore      = 0;

        foreach ($questions as $question) {
            $qId                   = $question->getQuestionId();
            $answer                = $answers[$qId] ?? '';
            $score                 = $this->parseAnswerToScore($answer, $question->getScale());
            $scores[$qId]          = $score;
            $originalAnswers[$qId] = $answer;
            $totalScore           += $score;
        }

        $riskLevel      = $this->determineRiskLevel($totalScore, (int)$assessmentId);
        $aiAnalysis     = $this->generateAIAnalysis($questions, $scores, $originalAnswers, $totalScore, $riskLevel);
        $interpretation = $this->generateInterpretation($riskLevel);
        $suggestSession = $this->shouldSuggestSession($riskLevel, $aiAnalysis);

        $sentimentData = [];
        if (!empty($freeText)) {
            try {
                $sentimentData = $this->groqService->analyzeSentiment($freeText);

                if (!empty($sentimentData['crisis_detected'])) {
                    $suggestSession = true;
                    if (!in_array(strtolower($riskLevel), ['high', 'severe'])) {
                        $riskLevel = 'High';
                    }
                }
            } catch (\Exception $e) {
                $sentimentData = [];
            }
        }

        $recommendedContent = $this->generateRecommendedContent($riskLevel, $aiAnalysis);

        $result = new AssessmentResult();
        $user   = $this->em->getRepository(User::class)->find((int)$userId);
        $result->setUser($user);
        $result->setAssessment($assessment);
        $result->setTotalScore($totalScore);
        $result->setRiskLevel($riskLevel);
        $result->setInterpretation($interpretation);
        $result->setRecommendedContent($recommendedContent);
        $result->setSuggestSession($suggestSession);
        $result->setTakenAt(new \DateTime());

        $this->em->persist($result);
        $this->em->flush();

        // ── ADD CRISIS ALERT IF HIGH RISK ─────────────────
        if (in_array(strtolower($riskLevel), ['high', 'severe', 'critical'])) {
            $this->crisisAlertService->addCrisisAlert($result);
        }

        $videos      = $this->youtubeService->fetchVideos($assessment->getType() ?? 'general', $riskLevel);
        $playlists   = $this->spotifyService->fetchPlaylists($assessment->getType() ?? 'general', $riskLevel);
        $meditations = $this->meditationService->getSessions($assessment->getType() ?? 'general', $riskLevel);

        return $this->render('result/show.html.twig', [
            'result'        => $result,
            'aiAnalysis'    => $aiAnalysis,
            'assessment'    => $assessment,
            'user'          => $user,
            'videos'        => $videos,
            'playlists'     => $playlists,
            'meditations'   => $meditations,
            'sentimentData' => $sentimentData,
            'freeText'      => $freeText,
        ]);
    }

    #[Route('/stats/{userId}', name: 'result_stats', methods: ['GET'])]
    public function stats(int $userId, UserRepository $userRepo): Response
    {
        $currentUser = $this->getCurrentUser();

        if ($this->isPatient() && $currentUser !== null
            && $currentUser->getId() !== $userId) {

            $this->addFlash('error', 'You can only view your own statistics.');
            return $this->redirectToRoute('result_index');
        }

        $results = $this->resultRepo->findByUser($userId);
        $user    = $userRepo->find($userId);

        $stats = [
            'totalAssessments' => count($results),
            'averageScore'     => 0,
            'highRiskCount'    => 0,
            'latestScore'      => null,
            'latestAssessment' => null,
            'trend'            => '',
        ];

        $riskBreakdown = [];

        if (!empty($results)) {
            $totalScore = 0;
            foreach ($results as $result) {
                $totalScore += $result->getTotalScore();
                $rl = $result->getRiskLevel() ?? 'Unknown';
                $riskBreakdown[$rl] = ($riskBreakdown[$rl] ?? 0) + 1;
                if (in_array(strtolower($rl), ['high', 'severe'])) {
                    $stats['highRiskCount']++;
                }
            }
            $stats['averageScore']     = round($totalScore / count($results), 1);
            $stats['latestAssessment'] = $results[0]->getTakenAt();
            $stats['latestScore']      = $results[0]->getTotalScore();

            if (count($results) >= 2) {
                $prev = $results[1]->getTotalScore();
                $curr = $results[0]->getTotalScore();
                $stats['trend'] = $curr > $prev ? '↑' : ($curr < $prev ? '↓' : '→');
            }
        }

        $chartData = [];
        $reversed  = array_reverse($results);
        foreach ($reversed as $result) {
            $chartData[] = [
                'date'  => $result->getTakenAt() ? $result->getTakenAt()->format('M d') : 'N/A',
                'score' => $result->getTotalScore(),
                'risk'  => $result->getRiskLevel(),
            ];
        }

        return $this->render('result/stats.html.twig', [
            'stats'         => $stats,
            'userId'        => $userId,
            'user'          => $user,
            'results'       => $results,
            'riskBreakdown' => $riskBreakdown,
            'chartData'     => $chartData,
        ]);
    }

    #[Route('/{id}/export-pdf', name: 'result_export_pdf', methods: ['GET'])]
    public function exportPdf(int $id): Response
    {
        $result = $this->resultRepo->find($id);

        if (!$result) {
            throw $this->createNotFoundException('Result not found');
        }

        if ($this->isPatient() && !$this->resultBelongsToCurrentUser($result)) {
            $this->addFlash('error', 'You can only export your own results.');
            return $this->redirectToRoute('result_index');
        }

        $user        = $result->getUser();
        $assessment  = $result->getAssessment();
        $aiAnalysis  = $result->getInterpretation() ?? '';

        $pdfContent = $this->pdfExportService->generateResultPdf(
            $result, $aiAnalysis, $user, $assessment
        );

        $filename = 'mentis-result-' . $result->getResultId() . '-' . date('Ymd') . '.pdf';

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($pdfContent),
        ]);
    }

    #[Route('/{id}/delete', name: 'result_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $result = $this->resultRepo->find($id);

        if (!$result) {
            throw $this->createNotFoundException('Result not found');
        }

        if ($this->isPatient() && !$this->resultBelongsToCurrentUser($result)) {
            $this->addFlash('error', 'You can only delete your own results.');
            return $this->redirectToRoute('result_index');
        }

        $this->em->remove($result);
        $this->em->flush();

        $this->addFlash('success', 'Result deleted successfully!');
        return $this->redirectToRoute('result_index');
    }

    #[Route('/{id}', name: 'result_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $result = $this->resultRepo->find($id);

        if (!$result) {
            throw $this->createNotFoundException('Result not found');
        }

        if ($this->isPatient() && !$this->resultBelongsToCurrentUser($result)) {
            $this->addFlash('error', 'You can only view your own results.');
            return $this->redirectToRoute('result_index');
        }

        $user        = $result->getUser();
        $assessment  = $result->getAssessment();
        $riskLevel   = $result->getRiskLevel() ?? 'low';
        $type        = $assessment?->getType() ?? 'general';

        $videos      = $this->youtubeService->fetchVideos($type, $riskLevel);
        $playlists   = $this->spotifyService->fetchPlaylists($type, $riskLevel);
        $meditations = $this->meditationService->getSessions($type, $riskLevel);

        return $this->render('result/show.html.twig', [
            'result'        => $result,
            'aiAnalysis'    => $result->getInterpretation() ?? '',
            'assessment'    => $assessment,
            'user'          => $user,
            'videos'        => $videos,
            'playlists'     => $playlists,
            'meditations'   => $meditations,
            'sentimentData' => [],
            'freeText'      => '',
        ]);
    }

    // ═══════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════════════

    private function generateAIAnalysis(array $questions, array $scores, array $originalAnswers, int $totalScore, string $riskLevel): string
{
    try {
        $prompt = $this->buildGroqPrompt($questions, $scores, $originalAnswers, $totalScore, $riskLevel);
        // Use generateAnalysis() NOT generateContent()
        return $this->groqService->generateAnalysis($prompt);
    } catch (\Exception $e) {
        return $this->generateRuleBasedAnalysis($questions, $scores, $originalAnswers, $totalScore, $riskLevel);
    }
}

    private function buildGroqPrompt(array $questions, array $scores, array $originalAnswers, int $totalScore, string $riskLevel): string
    {
        $maxPossible = count($questions) * 4;

        $prompt  = "You are a compassionate, professional mental health assessment analyst ";
        $prompt .= "working for the Mentis wellness platform. ";
        $prompt .= "A user has just completed a mental wellness self-assessment. ";
        $prompt .= "Write in plain English paragraphs only. ";
        $prompt .= "Do NOT use markdown symbols such as **, ##, *, or bullet points with dashes.\n\n";
        $prompt .= "=== ASSESSMENT DATA ===\n";
        $prompt .= "Total Score: {$totalScore} out of {$maxPossible}\n";
        $prompt .= "Risk Level: {$riskLevel}\n\n";
        $prompt .= "=== USER RESPONSES ===\n";

        foreach ($questions as $question) {
            $qId    = $question->getQuestionId();
            $score  = $scores[$qId] ?? 0;
            $answer = $originalAnswers[$qId] ?? '';
            $prompt .= "Question: " . $question->getText() . "\n";
            $prompt .= "Answer: {$answer} | Score: {$score}/4\n\n";
        }

        $prompt .= "Write a detailed, personalized mental health analysis with these sections:\n";
        $prompt .= "OVERALL SUMMARY, DETAILED RESPONSE ANALYSIS, PATTERNS DETECTED, ";
        $prompt .= "PERSONALIZED RECOMMENDATIONS, CLOSING NOTE\n";
        $prompt .= "Write minimum 500 words.";

        return $prompt;
    }

    private function generateRuleBasedAnalysis(array $questions, array $scores, array $originalAnswers, int $totalScore, string $riskLevel): string
    {
        $maxPossible  = count($questions) * 4;
        $percentage   = $maxPossible > 0 ? ($totalScore * 100.0 / $maxPossible) : 0;
        $averageScore = count($questions) > 0 ? $totalScore / count($questions) : 0;

        $analysis  = "OVERALL SUMMARY\n\n";
        $analysis .= sprintf(
            "You completed %d questions with a total score of %d out of %d points (%.0f%%). ",
            count($questions), $totalScore, $maxPossible, $percentage
        );
        $analysis .= "Your overall risk level has been assessed as: " . strtoupper($riskLevel) . ".\n\n";

        if ($averageScore <= 1.0) {
            $analysis .= "Your responses indicate that you are currently managing well across most areas assessed.\n\n";
        } elseif ($averageScore <= 2.0) {
            $analysis .= "Your responses suggest mild fluctuations in certain areas of your mental wellness.\n\n";
        } elseif ($averageScore <= 3.0) {
            $analysis .= "Your responses indicate moderate levels of distress or difficulty in several areas.\n\n";
        } else {
            $analysis .= "Your responses reflect significant levels of distress across multiple areas.\n\n";
        }

        $analysis .= "PERSONALIZED RECOMMENDATIONS\n\n";
        $riskLower = strtolower($riskLevel);

        if (in_array($riskLower, ['low', 'minimal'])) {
            $analysis .= "Focus on maintenance and prevention. Regular physical activity, consistent sleep schedules, and meaningful social connections are key.\n\n";
        } elseif (in_array($riskLower, ['mild', 'moderate'])) {
            $analysis .= "Proactive self-care is especially important. Mindfulness practices such as deep breathing, meditation, or yoga can help.\n\n";
        } else {
            $analysis .= "Your scores suggest that you would benefit significantly from professional support.\n\n";
        }

        $analysis .= "CLOSING NOTE\n\n";
        $analysis .= "Taking the time to reflect on your mental health is an act of genuine courage. This analysis is for informational purposes only and is not a clinical diagnosis.";

        return $analysis;
    }

    private function determineRiskLevel(int $totalScore, int $assessmentId): string
    {
        if ($assessmentId == 2) {
            if ($totalScore <= 3) return 'Minimal';
            if ($totalScore <= 6) return 'Mild';
            if ($totalScore <= 9) return 'Moderate';
            return 'Severe';
        }
        if ($assessmentId == 1) {
            if ($totalScore <= 4) return 'Low';
            if ($totalScore <= 8) return 'Moderate';
            return 'High';
        }
        $percentage = ($totalScore / 12) * 100;
        if ($percentage <= 25) return 'Low';
        if ($percentage <= 50) return 'Mild';
        if ($percentage <= 75) return 'Moderate';
        return 'High';
    }

    private function parseAnswerToScore(string $answer, ?string $scale): int
    {
        if (empty(trim($answer))) return 0;
        $answerLower = strtolower(trim($answer));

        if (str_contains($answer, ' - ')) {
            $parts = explode(' - ', $answer);
            if (is_numeric(trim($parts[0]))) return (int)trim($parts[0]);
        }
        if (is_numeric(trim($answer))) return (int)trim($answer);

        if (str_contains($answerLower, 'always') || str_contains($answerLower, 'very often') || str_contains($answerLower, 'nearly every day')) return 4;
        if (str_contains($answerLower, 'often')  || str_contains($answerLower, 'frequently')  || str_contains($answerLower, 'more than half'))   return 3;
        if (str_contains($answerLower, 'sometimes') || str_contains($answerLower, 'occasionally') || str_contains($answerLower, 'moderate'))      return 2;
        if (str_contains($answerLower, 'rarely') || str_contains($answerLower, 'seldom')   || str_contains($answerLower, 'a little'))             return 1;
        if (str_contains($answerLower, 'never')  || str_contains($answerLower, 'not at all'))                                                     return 0;
        if (str_contains($answerLower, 'yes')) return 1;
        if (str_contains($answerLower, 'no'))  return 0;

        return 2;
    }

    private function generateInterpretation(string $riskLevel): string
    {
        return match(strtolower($riskLevel)) {
            'low', 'minimal'   => 'Your scores indicate minimal concerns. Please review your AI analysis for personalized insights.',
            'moderate', 'mild' => 'Your scores suggest some areas that may need attention. Please review your AI analysis for personalized insights.',
            'high', 'severe'   => 'Your scores indicate significant concerns that should be addressed. Please review your AI analysis for personalized insights.',
            default            => 'Assessment completed. Please review your AI analysis for personalized insights.',
        };
    }

    private function generateRecommendedContent(string $riskLevel, string $aiAnalysis): string
    {
        $content   = "Based on your assessment results:\n";
        $riskLower = strtolower($riskLevel);

        if (in_array($riskLower, ['low', 'minimal'])) {
            $content .= "- Continue with healthy habits\n- Mindfulness practices for maintenance\n- Regular exercise routine\n";
        } elseif (in_array($riskLower, ['moderate', 'mild'])) {
            $content .= "- Stress management techniques\n- Self-help resources and books\n- Consider talking to a counselor\n";
            if (str_contains(strtolower($aiAnalysis), 'sleep')) $content .= "- Sleep hygiene improvement strategies\n";
            if (str_contains(strtolower($aiAnalysis), 'anxi'))  $content .= "- Anxiety reduction exercises\n";
        } else {
            $content .= "- Professional consultation recommended\n- Support groups available\n- Crisis hotline: 1-800-273-8255\n- Comprehensive evaluation suggested\n";
        }

        return $content;
    }

    private function shouldSuggestSession(string $riskLevel, string $aiAnalysis): bool
    {
        if (in_array(strtolower($riskLevel), ['high', 'severe'])) return true;
        $lower = strtolower($aiAnalysis);
        return str_contains($lower, 'professional') && str_contains($lower, 'significant');
    }
}