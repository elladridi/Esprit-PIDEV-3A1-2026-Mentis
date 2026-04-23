<?php

namespace App\Controller;

use App\Entity\Question;
use App\Form\QuestionType;
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
        $this->em             = $em;
        $this->questionRepo   = $questionRepo;
        $this->assessmentRepo = $assessmentRepo;
    }

    #[Route('/', name: 'question_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('question/index.html.twig', [
            'questions' => $this->questionRepo->findAll(),
        ]);
    }

    #[Route('/assessment/{assessmentId}', name: 'question_by_assessment', methods: ['GET'])]
    public function byAssessment(int $assessmentId): Response
    {
        $assessment = $this->assessmentRepo->find($assessmentId);
        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        return $this->render('question/index.html.twig', [
            'questions'  => $this->questionRepo->findByAssessment($assessmentId),
            'assessment' => $assessment,
        ]);
    }

    #[Route('/new', name: 'question_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $question = new Question();

        // Pre-select assessment if passed via query string
        $assessmentId = $request->query->get('assessmentId');
        if ($assessmentId) {
            $assessment = $this->assessmentRepo->find($assessmentId);
            if ($assessment) {
                $question->setAssessment($assessment);
            }
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($question);
            $this->em->flush();

            $this->addFlash('success', 'Question created successfully!');
            return $this->redirectToRoute('question_by_assessment', [
                'assessmentId' => $question->getAssessment()->getAssessmentId(),
            ]);
        }

        return $this->render('question/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $question = $this->questionRepo->find($id);
        if (!$question) {
            throw $this->createNotFoundException('Question not found');
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Question updated successfully!');
            return $this->redirectToRoute('question_by_assessment', [
                'assessmentId' => $question->getAssessment()->getAssessmentId(),
            ]);
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'form'     => $form->createView(),
        ]);
    }

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

        return $assessmentId
            ? $this->redirectToRoute('question_by_assessment', ['assessmentId' => $assessmentId])
            : $this->redirectToRoute('question_index');
    }

    #[Route('/count/{assessmentId}', name: 'question_count', methods: ['GET'])]
    public function count(int $assessmentId): Response
    {
        return $this->json(['count' => count($this->questionRepo->findByAssessment($assessmentId))]);
    }
}