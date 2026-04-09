<?php

namespace App\Controller;

use App\Entity\Goal;
use App\Form\GoalType;
use App\Repository\GoalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/goal')]
#[IsGranted('ROLE_USER')]
class GoalController extends AbstractController
{
    public function __construct(
        private GoalRepository $goalRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_goal_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $goals = $this->goalRepository->findByUserOrdered($user);
        $incompleteGoals = $this->goalRepository->findIncompleteByUser($user);
        $completedGoals = $this->goalRepository->findCompletedByUser($user);

        return $this->render('goal/index.html.twig', [
            'goals' => $goals,
            'incompleteGoals' => $incompleteGoals,
            'completedGoals' => $completedGoals,
        ]);
    }

    #[Route('/new', name: 'app_goal_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $goal = new Goal();
        $goal->setUser($user);

        $form = $this->createForm(GoalType::class, $goal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($goal);
            $this->entityManager->flush();

            $this->addFlash('success', 'Goal created successfully!');

            return $this->redirectToRoute('app_goal_index');
        }

        return $this->render('goal/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_goal_show', methods: ['GET'])]
    public function show(Goal $goal): Response
    {
        $user = $this->getUser();
        if (!$user || $goal->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('goal/show.html.twig', [
            'goal' => $goal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_goal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Goal $goal): Response
    {
        $user = $this->getUser();
        if (!$user || $goal->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(GoalType::class, $goal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Goal updated successfully!');

            return $this->redirectToRoute('app_goal_show', ['id' => $goal->getId()]);
        }

        return $this->render('goal/edit.html.twig', [
            'goal' => $goal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_goal_delete', methods: ['POST'])]
    public function delete(Request $request, Goal $goal): Response
    {
        $user = $this->getUser();
        if (!$user || $goal->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $goal->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($goal);
            $this->entityManager->flush();

            $this->addFlash('success', 'Goal deleted successfully!');
        }

        return $this->redirectToRoute('app_goal_index');
    }

    #[Route('/{id}/toggle', name: 'app_goal_toggle', methods: ['POST'])]
    public function toggle(Request $request, Goal $goal): Response
    {
        $user = $this->getUser();
        if (!$user || $goal->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('toggle' . $goal->getId(), $request->request->get('_token'))) {
            $goal->setIsCompleted(!$goal->isCompleted());
            $this->entityManager->flush();

            $message = $goal->isCompleted() ? 'Goal marked as completed!' : 'Goal marked as incomplete!';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_goal_index');
    }
}
