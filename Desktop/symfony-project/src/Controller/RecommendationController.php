<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PersonalizedRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/recommendations')]
class RecommendationController extends AbstractController
{
    #[Route('/', name: 'recommendations_index')]
    #[IsGranted('ROLE_USER')]
    public function index(PersonalizedRecommendationService $recommendationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getType() !== 'Patient') {
            return $this->redirectToRoute('app_dashboard');
        }
        
        $recommendations = $recommendationService->getRecommendations($user);
        
        // Debug: Check if we have sessions
        $hasReserved = $recommendations['reserved_count'] ?? 0;
        $hasPast = $recommendations['past_count'] ?? 0;
        
        return $this->render('recommendations/index.html.twig', [
            'recommendations' => $recommendations,
            'has_reserved_sessions' => $hasReserved > 0,
            'has_past_sessions' => $hasPast > 0,
            'refreshed' => false,
        ]);
    }
    
    #[Route('/refresh', name: 'recommendations_refresh')]
    #[IsGranted('ROLE_USER')]
    public function refresh(PersonalizedRecommendationService $recommendationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->getType() !== 'Patient') {
            return $this->redirectToRoute('app_dashboard');
        }
        
        $recommendations = $recommendationService->getRecommendations($user);
        
        return $this->render('recommendations/index.html.twig', [
            'recommendations' => $recommendations,
            'refreshed' => true,
        ]);
    }
}