<?php

namespace App\Controller;

use App\Service\SessionAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics')]
#[IsGranted('ROLE_ADMIN')]
class AdminAnalyticsController extends AbstractController
{
    #[Route('/', name: 'admin_analytics')]
    public function index(SessionAnalyticsService $analyticsService): Response
    {
        $analytics = $analyticsService->getSessionAnalytics();
        
        return $this->render('admin/analytics.html.twig', [
            'analytics' => $analytics,
        ]);
    }
}