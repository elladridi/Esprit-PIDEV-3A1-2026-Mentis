<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Form\MoodType;
use App\Repository\MoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mood')]
#[IsGranted('ROLE_USER')]
class MoodController extends AbstractController
{
    public function __construct(
        private MoodRepository $moodRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_mood_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $moods = $this->moodRepository->findByUserOrdered($user);

        return $this->render('mood/index.html.twig', [
            'moods' => $moods,
        ]);
    }

    #[Route('/new', name: 'app_mood_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $mood = new Mood();
        $mood->setUser($user);

        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($mood);
            $this->entityManager->flush();

            $this->addFlash('success', 'Mood recorded successfully!');

            return $this->redirectToRoute('app_mood_index');
        }

        return $this->render('mood/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mood_show', methods: ['GET'])]
    public function show(Mood $mood): Response
    {
        $user = $this->getUser();
        if (!$user || $mood->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('mood/show.html.twig', [
            'mood' => $mood,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mood_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mood $mood): Response
    {
        $user = $this->getUser();
        if (!$user || $mood->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Mood updated successfully!');

            return $this->redirectToRoute('app_mood_show', ['id' => $mood->getId()]);
        }

        return $this->render('mood/edit.html.twig', [
            'mood' => $mood,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_mood_delete', methods: ['POST'])]
    public function delete(Request $request, Mood $mood): Response
    {
        $user = $this->getUser();
        if (!$user || $mood->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $mood->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($mood);
            $this->entityManager->flush();

            $this->addFlash('success', 'Mood deleted successfully!');
        }

        return $this->redirectToRoute('app_mood_index');
    }
}
