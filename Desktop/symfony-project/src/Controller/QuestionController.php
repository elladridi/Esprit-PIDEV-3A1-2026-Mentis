<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\AssessmentRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/question')]
class QuestionController extends AbstractController
{
    private EntityManagerInterface $em;
    private QuestionRepository $questionRepo;
    private AssessmentRepository $assessmentRepo;

    public function __construct(
        EntityManagerInterface $em,
        QuestionRepository $questionRepo,
        AssessmentRepository $assessmentRepo
    ) {
        $this->em = $em;
        $this->questionRepo = $questionRepo;
        $this->assessmentRepo = $assessmentRepo;
    }

    // ── LIST ALL ──────────────────────────────────────────────────
    #[Route('/', name: 'question_index', methods: ['GET'])]
    public function index(): Response
    {
        $questions = $this->questionRepo->findAll();
        return $this->render('question/index.html.twig', [
            'questions' => $questions,
        ]);
    }

    // ── LIST BY ASSESSMENT ────────────────────────────────────────
    #[Route('/assessment/{assessmentId}', name: 'question_by_assessment', methods: ['GET'])]
    public function byAssessment(int $assessmentId): Response
    {
        $assessment = $this->assessmentRepo->find($assessmentId);

        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        $questions = $this->questionRepo->findByAssessment($assessmentId);

        return $this->render('question/index.html.twig', [
            'questions' => $questions,
            'assessment' => $assessment,
        ]);
    }

    // ── CREATE ────────────────────────────────────────────────────
    #[Route('/new', name: 'question_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $assessments = $this->assessmentRepo->findAll();
        $assessmentId = $request->query->get('assessmentId');

        if ($request->isMethod('POST')) {
            $assessmentId = $request->request->get('assessment_id');
            $assessment = $this->assessmentRepo->find($assessmentId);

            if (!$assessment) {
                $this->addFlash('error', 'Assessment not found');
                return $this->redirectToRoute('question_new');
            }

            $question = new Question();
            $question->setAssessment($assessment);
            $question->setText($request->request->get('text'));
            $question->setScale($request->request->get('scale'));

            $this->em->persist($question);
            $this->em->flush();

            $this->addFlash('success', 'Question created successfully!');
            return $this->redirectToRoute('question_by_assessment', [
                'assessmentId' => $assessmentId
            ]);
        }

        return $this->render('question/new.html.twig', [
            'assessments' => $assessments,
            'selectedAssessmentId' => $assessmentId,
        ]);
    }

    // ── EDIT ──────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $question = $this->questionRepo->find($id);

        if (!$question) {
            throw $this->createNotFoundException('Question not found');
        }

        $assessments = $this->assessmentRepo->findAll();

        if ($request->isMethod('POST')) {
            $assessmentId = $request->request->get('assessment_id');
            $assessment = $this->assessmentRepo->find($assessmentId);

            if ($assessment) {
                $question->setAssessment($assessment);
            }

            $question->setText($request->request->get('text'));
            $question->setScale($request->request->get('scale'));

            $this->em->flush();

            $this->addFlash('success', 'Question updated successfully!');
            return $this->redirectToRoute('question_by_assessment', [
                'assessmentId' => $question->getAssessment()->getAssessmentId()
            ]);
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'assessments' => $assessments,
        ]);
    }

    // ── DELETE ────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'question_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $question = $this->questionRepo->find($id);

        if (!$question) {
            throw $this->createNotFoundException('Question not found');
        }

        $assessmentId = $question->getAssessment()?->getAssessmentId();

        $this->em->remove($question);
        $this->em->flush();

        $this->addFlash('success', 'Question deleted successfully!');

        if ($assessmentId) {
            return $this->redirectToRoute('question_by_assessment', [
                'assessmentId' => $assessmentId
            ]);
        }

        return $this->redirectToRoute('question_index');
    }

    // ── COUNT BY ASSESSMENT ───────────────────────────────────────
    #[Route('/count/{assessmentId}', name: 'question_count', methods: ['GET'])]
    public function count(int $assessmentId): Response
    {
        $questions = $this->questionRepo->findByAssessment($assessmentId);
        return $this->json(['count' => count($questions)]);
    }
}