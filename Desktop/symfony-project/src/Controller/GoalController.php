<?php

namespace App\Controller;

use App\Entity\Goal;
use App\Form\GoalType;
use App\Repository\GoalRepository;
use App\Service\EmailManager;
use App\Service\GoalAIService;
use App\Service\GoalQrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/goal')]
#[IsGranted('ROLE_USER')]
class GoalController extends AbstractController
{
    #[Route('/', name: 'app_goal_index', methods: ['GET'])]
    public function index(Request $request, GoalRepository $goalRepository): Response
    {
        $query = $request->query->get('q');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');

        $user = $this->getUser();
        $goals = $goalRepository->findFilteredForUser($user, $query, $status, $sort);
        $incompleteGoals = $goalRepository->findIncompleteByUser($user);
        $completedGoals = $goalRepository->findCompletedByUser($user);

        return $this->render('goal/index.html.twig', [
            'goals' => $goals,
            'incompleteGoals' => $incompleteGoals,
            'completedGoals' => $completedGoals,
            'current_query' => $query,
            'current_status' => $status,
            'current_sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_goal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $goal = new Goal();
        $goal->setUser($this->getUser());
        $form = $this->createForm(GoalType::class, $goal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$goal->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($goal);
            $entityManager->flush();

            return $this->redirectToRoute('app_goal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('goal/new.html.twig', [
            'goal' => $goal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_goal_show', methods: ['GET'])]
    public function show(Goal $goal, GoalQrCodeService $qrCodeService): Response
    {
        $this->denyAccessUnlessGranted('view', $goal);

        return $this->render('goal/show.html.twig', [
            'goal' => $goal,
            'qrCode' => $qrCodeService->generateQrCode($goal),
        ]);
    }

    #[Route('/{id}/qr-view', name: 'app_goal_qr_view', methods: ['GET'])]
    public function publicShow(Request $request, Goal $goal): Response
    {
        $token = $request->query->get('token');
        $expectedToken = hash_hmac('sha256', (string) $goal->getId(), $this->getParameter('kernel.secret'));

        if (!hash_equals($expectedToken, $token ?? '')) {
            throw $this->createAccessDeniedException('Invalid or missing secure token for public view.');
        }

        return $this->render('goal/public_show.html.twig', [
            'goal' => $goal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_goal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Goal $goal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $goal);

        $form = $this->createForm(GoalType::class, $goal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_goal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('goal/edit.html.twig', [
            'goal' => $goal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_goal_delete', methods: ['POST'])]
    public function delete(Request $request, Goal $goal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $goal);

        if ($this->isCsrfTokenValid('delete'.$goal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($goal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_goal_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle', name: 'app_goal_toggle', methods: ['POST'])]
    public function toggle(Request $request, Goal $goal, EntityManagerInterface $entityManager, EmailManager $emailManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $goal);

        $goal->setIsCompleted(!$goal->isCompleted());
        $entityManager->flush();

        if ($goal->isCompleted()) {
            $emailManager->sendGoalCompletedEmail($goal->getUser(), $goal);
            $this->addFlash('success', 'Goal marked as completed! Check your email for a celebration.');
        } else {
            $this->addFlash('info', 'Goal marked as pending.');
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_goal_index');
    }

    #[Route('/{id}/analyze', name: 'app_goal_analyze', methods: ['POST'])]
    public function analyze(Goal $goal, GoalAIService $aiService, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('view', $goal);

        $advice = $aiService->getGoalAdvice($goal);
        $goal->setAiAdvice($advice);
        $entityManager->flush();

        $this->addFlash('success', 'AI Analysis complete!');

        return $this->redirectToRoute('app_goal_show', ['id' => $goal->getId()]);
    }
}
