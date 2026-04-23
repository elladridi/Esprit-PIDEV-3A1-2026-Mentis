<?php

namespace App\Controller;

use App\Entity\Assessment;
use App\Entity\AssessmentResult;
use App\Entity\Question;
use App\Repository\AssessmentRepository;
use App\Repository\QuestionRepository;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/take')]
class TakeAssessmentController extends AbstractController
{
    private AssessmentRepository $assessmentRepo;
    private QuestionRepository $questionRepo;
    private GroqService $groqService;
    private EntityManagerInterface $em;

    public function __construct(
        AssessmentRepository $assessmentRepo,
        QuestionRepository $questionRepo,
        GroqService $groqService,
        EntityManagerInterface $em
    ) {
        $this->assessmentRepo = $assessmentRepo;
        $this->questionRepo   = $questionRepo;
        $this->groqService    = $groqService;
        $this->em             = $em;
    }

    // ── 🔥 ASSESSMENT SELECTION WITH FULL QB FILTERS + SORT ───────
    #[Route('/', name: 'take_assessment_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'title_asc');

        // 🔥 DYNAMIC QUERY BUILDER - FIXED FIELD NAMES
        $qb = $this->assessmentRepo->createQueryBuilder('a')
            ->leftJoin('a.questions', 'q')
            ->select('a', 'COUNT(q.questionId) as HIDDEN questionCount')
            ->groupBy('a.assessmentId')
            ->orderBy('a.title', 'ASC');

        // 🔍 FULL-TEXT SEARCH (title + description)
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.title', ':search'),
                    $qb->expr()->like('a.description', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }

        // 📂 TYPE FILTER
        if ($type) {
            $qb->andWhere('a.type = :type')->setParameter('type', $type);
        }

        // ✅ STATUS FILTER (Active by default)
        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        } else {
            $qb->andWhere('a.status = :active')->setParameter('active', 'Active');
        }

        // 🔄 DYNAMIC SORTING
        switch ($sort) {
            case 'title_asc':
                $qb->orderBy('a.title', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('a.title', 'DESC');
                break;
            case 'type_asc':
                $qb->orderBy('a.type', 'ASC');
                break;
            case 'created_at_desc':
                $qb->orderBy('a.createdAt', 'DESC');
                break;
            case 'questions_desc':
                $qb->orderBy('questionCount', 'DESC');
                break;
            default:
                $qb->orderBy('a.title', 'ASC');
        }

        // EXECUTE QUERY
        $assessments = $qb->getQuery()->getResult();

        // 📊 TOTAL COUNT QUERY (separate for accuracy)
        $countQB = $this->assessmentRepo->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.assessmentId)')
            ->where('a.status = :active')
            ->setParameter('active', 'Active');
        
        if ($search) {
            $countQB->andWhere(
                $countQB->expr()->orX(
                    $countQB->expr()->like('a.title', ':search'),
                    $countQB->expr()->like('a.description', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }
        if ($type) {
            $countQB->andWhere('a.type = :type')->setParameter('type', $type);
        }
        if ($status) {
            $countQB->andWhere('a.status = :status')->setParameter('status', $status);
        }

        $totalCount = (int) $countQB->getQuery()->getSingleScalarResult();

        return $this->render('take_assessment/index.html.twig', [
            'assessments' => $assessments,
            'search'      => $search,
            'type'        => $type,
            'status'      => $status,
            'sort'        => $sort,
            'totalCount'  => $totalCount,
        ]);
    }

    // ── TAKE SPECIFIC ASSESSMENT ──────────────────────────────────
    #[Route('/{id}', name: 'take_assessment_start', methods: ['GET'])]
    public function start(int $id): Response
    {
        $assessment = $this->assessmentRepo->find($id);

        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        // ✅ CHECK IF ACTIVE
        if ($assessment->getStatus() !== 'Active') {
            $this->addFlash('error', 'This assessment is currently inactive.');
            return $this->redirectToRoute('take_assessment_index');
        }

        $questions = $this->questionRepo->findByAssessment($id);

        if (empty($questions)) {
            $this->addFlash('error', 'This assessment has no questions yet.');
            return $this->redirectToRoute('take_assessment_index');
        }

        // Parse scale options for each question
        $questionData = [];
        foreach ($questions as $question) {
            $questionData[] = [
                'id'      => $question->getQuestionId(),
                'text'    => $question->getText(),
                'scale'   => $question->getScale(),
                'options' => $this->parseScaleToOptions($question->getScale()),
            ];
        }

        return $this->render('take_assessment/take.html.twig', [
            'assessment' => $assessment,
            'questions'  => $questionData,
            'total'      => count($questionData),
        ]);
    }

    // ── GENERATE ADAPTIVE QUESTION VIA AI ─────────────────────────
    #[Route('/ai/adaptive-question', name: 'take_assessment_adaptive', methods: ['POST'])]
    public function generateAdaptiveQuestion(Request $request): JsonResponse
    {
        $data         = json_decode($request->getContent(), true);
        $categoryScores = $data['categoryScores'] ?? [];
        $focus        = $data['focus'] ?? 'general';
        $askedIds     = $data['askedIds'] ?? [];

        // Build context based on previous answers
        $prompt = "Based on a mental health assessment where the patient has shown: ";

        foreach ($categoryScores as $category => $score) {
            $level    = $score > 0.7 ? 'high' : ($score > 0.4 ? 'moderate' : 'low');
            $prompt  .= "{$category} ({$level} levels), ";
        }

        $prompt   .= "\n\nGenerate ONE specific follow-up question about {$focus}. ";
        $prompt   .= "The question MUST be answerable using a simple scale. ";
        $prompt   .= "Return ONLY the question text, nothing else.";

        try {
            $questionText = $this->groqService->generateContent($prompt);
            $questionText = trim(preg_replace('/^\d+\.\s*/', '', $questionText));
            $questionText = trim($questionText, '"*');

            return $this->json([
                'success'  => true,
                'question' => [
                    'id'      => 'ai_' . time(),
                    'text'    => $questionText,
                    'scale'   => 'Never/Rarely/Sometimes/Often/Always',
                    'options' => ['Never', 'Rarely', 'Sometimes', 'Often', 'Always'],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── AI QUESTION GENERATOR FOR ADMIN ───────────────────────────
    #[Route('/ai/generate-questions', name: 'take_assessment_generate_questions', methods: ['POST'])]
    public function generateQuestions(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $count   = (int)($data['count'] ?? 5);
        $focus   = $data['focus'] ?? 'General mental wellness';
        $scale   = $data['scale'] ?? 'Never/Rarely/Sometimes/Often/Always';
        $context = $data['context'] ?? '';

        // Log the request for debugging
        error_log('=== GENERATE QUESTIONS REQUEST ===');
        error_log('Count: ' . $count);
        error_log('Focus: ' . $focus);
        error_log('Scale: ' . $scale);
        error_log('Context: ' . $context);

        $prompt  = "You are an expert clinical psychologist and mental health assessment designer. ";
        $prompt .= "Generate exactly {$count} original mental health assessment questions focused on: {$focus}. ";

        if ($context) {
            $prompt .= "Additional context: {$context}. ";
        }

        $prompt .= "Each question must use this answer scale: {$scale}.\n\n";
        $prompt .= "CRITICAL FORMAT RULES:\n";
        $prompt .= "- Number each question like: 1. [question text]\n";
        $prompt .= "- After each question write: SCALE: {$scale}\n";
        $prompt .= "- Questions MUST be answerable with a single scale selection\n";
        $prompt .= "- DO NOT create questions that ask for explanations or descriptions\n";
        $prompt .= "- Output ONLY the questions, nothing else.\n";
        $prompt .= "- Questions should be in first-person.\n\n";
        $prompt .= "Now generate {$count} questions:";

        try {
            error_log('Calling Groq API with generateContent()...');
            
            $response = $this->groqService->generateContent($prompt);
            
            // Log the raw response for debugging
            error_log('Raw Groq Response: ' . substr($response, 0, 500));
            
            $questions = $this->parseGeneratedQuestions($response, $scale);
            
            error_log('Parsed ' . count($questions) . ' questions');

            return $this->json([
                'success'   => true,
                'questions' => $questions,
            ]);
        } catch (\Exception $e) {
            error_log('ERROR in generateQuestions: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => $e->getMessage()
            ]);
        }
    }

    // ── SAVE AI GENERATED QUESTIONS ───────────────────────────────
    #[Route('/ai/save-questions/{assessmentId}', name: 'take_assessment_save_questions', methods: ['POST'])]
    public function saveGeneratedQuestions(Request $request, int $assessmentId): JsonResponse
    {
        $assessment = $this->assessmentRepo->find($assessmentId);
        if (!$assessment) {
            return $this->json(['success' => false, 'error' => 'Assessment not found']);
        }

        $data      = json_decode($request->getContent(), true);
        $questions = $data['questions'] ?? [];
        $saved     = 0;

        foreach ($questions as $qData) {
            $text  = trim($qData['text'] ?? '');
            $scale = trim($qData['scale'] ?? 'Never/Rarely/Sometimes/Often/Always');

            if (empty($text)) continue;

            $question = new \App\Entity\Question();
            $question->setAssessment($assessment);
            $question->setText($text);
            $question->setScale($scale);

            $this->em->persist($question);
            $saved++;
        }

        $this->em->flush();

        return $this->json(['success' => true, 'saved' => $saved]);
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    private function parseScaleToOptions(?string $scale): array
    {
        if (empty($scale)) return ['1', '2', '3', '4', '5'];

        $scale = trim($scale);

        if (str_contains($scale, '=')) {
            $items   = explode(',', $scale);
            $options = [];
            foreach ($items as $item) {
                if (str_contains($item, '=')) {
                    $parts = explode('=', $item, 2);
                    if (count($parts) === 2) {
                        $options[] = trim($parts[0]) . ' - ' . trim($parts[1]);
                    }
                }
            }
            if (!empty($options)) return $options;
        }

        if (str_contains($scale, '/')) {
            return array_map('trim', explode('/', $scale));
        }

        if (preg_match('/^(\d+)-(\d+)$/', trim($scale), $m)) {
            $options = [];
            for ($i = (int)$m[1]; $i <= (int)$m[2]; $i++) {
                $options[] = (string)$i;
            }
            return $options;
        }

        return ['1', '2', '3', '4', '5'];
    }

    private function parseGeneratedQuestions(string $response, string $defaultScale): array
    {
        $lines           = explode("\n", $response);
        $questions       = [];
        $currentQuestion = null;
        $currentScale    = $defaultScale;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/^\d+\.\s+(.+)/', $line, $m)) {
                if ($currentQuestion !== null) {
                    $questions[] = [
                        'text'  => trim($currentQuestion),
                        'scale' => $currentScale,
                    ];
                }
                $currentQuestion = $m[1];
                $currentScale    = $defaultScale;
            } elseif (strtoupper(substr($line, 0, 6)) === 'SCALE:') {
                $currentScale = trim(substr($line, 6));
            } elseif ($currentQuestion !== null) {
                $currentQuestion .= ' ' . $line;
            }
        }

        if ($currentQuestion !== null) {
            $questions[] = [
                'text'  => trim($currentQuestion),
                'scale' => $currentScale,
            ];
        }

        return $questions;
    }
}