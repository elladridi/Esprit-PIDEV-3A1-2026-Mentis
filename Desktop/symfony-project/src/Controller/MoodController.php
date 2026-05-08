<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Form\MoodType;
use App\Repository\MoodRepository;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mood')]
#[IsGranted('ROLE_USER')]
class MoodController extends AbstractController
{
    #[Route('/', name: 'app_mood_index', methods: ['GET'])]
    public function index(Request $request, MoodRepository $moodRepository): Response
    {
        $query = $request->query->get('q');
        $feeling = $request->query->get('feeling');
        $sort = $request->query->get('sort', 'newest');

        $moods = $moodRepository->findFilteredForUser($this->getUser(), $query, $feeling, $sort);

        return $this->render('mood/index.html.twig', [
            'moods' => $moods,
            'current_query' => $query,
            'current_feeling' => $feeling,
            'current_sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_mood_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SpotifyService $spotifyService): Response
    {
        $mood = new Mood();
        $mood->setUser($this->getUser());
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate Spotify recommendation
            $recommendation = $spotifyService->getRecommendationForMood($mood->getFeeling());
            $mood->setRecommendedTrackName($recommendation['track_name']);
            $mood->setRecommendedArtistName($recommendation['artist_name']);
            $mood->setRecommendedTrackUrl($recommendation['track_url']);
            $mood->setRecommendedPreviewUrl($recommendation['preview_url']);
            $mood->setRecommendedImageUrl($recommendation['image_url']);

            $entityManager->persist($mood);
            $entityManager->flush();

            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mood/new.html.twig', [
            'mood' => $mood,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mood_show', methods: ['GET'])]
    public function show(Mood $mood): Response
    {
        $this->denyAccessUnlessGranted('view', $mood);

        return $this->render('mood/show.html.twig', [
            'mood' => $mood,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mood_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mood $mood, EntityManagerInterface $entityManager, SpotifyService $spotifyService): Response
    {
        $this->denyAccessUnlessGranted('edit', $mood);

        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Refresh Spotify recommendation if feeling changed
            $recommendation = $spotifyService->getRecommendationForMood($mood->getFeeling());
            $mood->setRecommendedTrackName($recommendation['track_name']);
            $mood->setRecommendedArtistName($recommendation['artist_name']);
            $mood->setRecommendedTrackUrl($recommendation['track_url']);
            $mood->setRecommendedPreviewUrl($recommendation['preview_url']);
            $mood->setRecommendedImageUrl($recommendation['image_url']);

            $entityManager->flush();

            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mood/edit.html.twig', [
            'mood' => $mood,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mood_delete', methods: ['POST'])]
    public function delete(Request $request, Mood $mood, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $mood);

        if ($this->isCsrfTokenValid('delete'.$mood->getId(), $request->request->get('_token'))) {
            $entityManager->remove($mood);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
    }
}
