<?php

namespace App\Controller;

use App\AnalyticsBundle\Repository\GoalRepository;
use App\AnalyticsBundle\Repository\MoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardStatsController extends AbstractController
{
    #[Route('/analytics', name: 'analytics_dashboard_stats')]
    public function index(MoodRepository $moodRepository, GoalRepository $goalRepository): Response
    {
        /** @var UserInterface $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');

        }

        // Appel des méthodes personnalisées du repository du bundle
        $moodCounts = $moodRepository->getFeelingCountsForUser($user);
        $goalStatusCounts = $goalRepository->getStatusCountsForUser($user);
        
        $sevenDaysAgo = new \DateTime('-7 days');
        $moodTrend = $this->groupMoodsByDay($moodRepository->findCreatedSinceByUser($user, $sevenDaysAgo));
        $goalTrend = $this->groupGoalsByDay($goalRepository->findCreatedSinceByUser($user, $sevenDaysAgo));

        return $this->render('dashboard/stats.html.twig', [
            'moodDist' => $moodCounts,
            'goalStats' => $goalStatusCounts,
            'moodEvolution' => $moodTrend,
            'goalEvolution' => $goalTrend,
        ]);
    }

    private function groupMoodsByDay(array $moods): array
    {
        $evolution = [];
        $moodValues = ['very_sad' => 1, 'sad' => 2, 'neutral' => 3, 'happy' => 4, 'very_happy' => 5];
        foreach ($moods as $mood) {
            $date = $mood->getCreatedAt()->format('M d');
            $evolution[] = ['date' => $date, 'value' => $moodValues[$mood->getFeeling()] ?? 3];
        }
        return $evolution;
    }

    private function groupGoalsByDay(array $goals): array
    {
        $evolution = [];
        foreach ($goals as $goal) {
            $date = $goal->getCreatedAt()->format('M d');
            $evolution[$date] = ($evolution[$date] ?? 0) + 1;
        }
        $formatted = [];
        foreach ($evolution as $date => $count) {
            $formatted[] = ['date' => $date, 'count' => $count];
        }
        return $formatted;
    }
}