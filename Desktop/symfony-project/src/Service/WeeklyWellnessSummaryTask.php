<?php

namespace App\Service;

use App\Entity\WellnessNotification;
use App\Repository\UserRepository;
use App\Repository\GoalRepository;
use App\Repository\MoodRepository;
use App\Repository\WellnessNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class WeeklyWellnessSummaryTask
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private GoalRepository $goalRepo,
        private MoodRepository $moodRepo,
        private WellnessNotificationRepository $notifRepo,
        private EmailManager $emailManager
    ) {}

    public function run(?int $userId = null): int
    {
        $users = $userId ? [$this->userRepo->find($userId)] : $this->userRepo->findAll();
        $lastWeek = new \DateTime('-7 days');
        $count = 0;

        foreach ($users as $user) {
            if (!$user) continue;

            $moods = $this->moodRepo->createQueryBuilder('m')
                ->where('m.user = :user')
                ->andWhere('m.createdAt >= :date')
                ->setParameter('user', $user)
                ->setParameter('date', $lastWeek)
                ->getQuery()
                ->getResult();

            $goalsCompleted = $this->goalRepo->createQueryBuilder('g')
                ->select('count(g.id)')
                ->where('g.user = :user')
                ->andWhere('g.isCompleted = true')
                ->andWhere('g.updatedAt >= :date')
                ->setParameter('user', $user)
                ->setParameter('date', $lastWeek)
                ->getQuery()
                ->getSingleScalarResult();

            $goalsPending = $this->goalRepo->createQueryBuilder('g')
                ->select('count(g.id)')
                ->where('g.user = :user')
                ->andWhere('g.isCompleted = false')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();

            $moodCounts = [];
            foreach ($moods as $m) {
                $moodCounts[$m->getFeeling()] = ($moodCounts[$m->getFeeling()] ?? 0) + 1;
            }
            arsort($moodCounts);
            $topMood = key($moodCounts) ?: 'N/A';

            $stats = [
                'mood_count' => count($moods),
                'most_frequent_mood' => $topMood,
                'completed_goals' => $goalsCompleted,
                'pending_goals' => $goalsPending,
            ];

            $content = "Weekly Summary: You logged " . count($moods) . " moods this week. Your most frequent mood was " . str_replace('_', ' ', $topMood) . ". You completed $goalsCompleted goals!";
            
            $fingerprint = "weekly_summary_" . $user->getId() . "_" . date('W-Y');
            $existing = $this->notifRepo->findOneBy(['fingerprint' => $fingerprint]);
            if ($existing) continue;

            $notification = $this->createNotification($user, WellnessNotification::TYPE_WEEKLY_SUMMARY, $content, $fingerprint, $stats);
            $this->emailManager->sendWeeklyWellnessSummary($user, $stats, $notification);
            $this->em->flush();
            $count++;
        }

        return $count;
    }

    private function createNotification($user, string $type, string $content, ?string $fingerprint = null, ?array $data = null): WellnessNotification
    {
        $notif = new WellnessNotification();
        $notif->setUser($user);
        $notif->setType($type);
        $notif->setContent($content);
        $notif->setFingerprint($fingerprint);
        $notif->setData($data);

        $this->em->persist($notif);
        return $notif;
    }
}
