<?php

namespace App\Service;

use App\Entity\WellnessNotification;
use App\Repository\GoalRepository;
use App\Repository\WellnessNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class GoalDeadlineReminderTask
{
    public function __construct(
        private EntityManagerInterface $em,
        private GoalRepository $goalRepo,
        private WellnessNotificationRepository $notifRepo,
        private EmailManager $emailManager
    ) {}

    public function run(?int $userId = null): int
    {
        $tomorrow = new \DateTime('+1 day');
        $qb = $this->goalRepo->createQueryBuilder('g')
            ->where('g.isCompleted = false')
            ->andWhere('g.deadline <= :tomorrow');
        
        if ($userId) {
            $qb->andWhere('g.user = :userId')
               ->setParameter('userId', $userId);
        }

        $goals = $qb->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($goals as $goal) {
            $user = $goal->getUser();
            $fingerprint = "goal_deadline_" . $goal->getId() . "_" . $tomorrow->format('Y-m-d');
            
            $existing = $this->notifRepo->findOneBy(['fingerprint' => $fingerprint]);
            if ($existing) continue;

            $notification = $this->createNotification($user, WellnessNotification::TYPE_GOAL_DEADLINE_REMINDER, "Reminder: Your goal '{$goal->getTitle()}' is due soon!", $fingerprint);
            $this->emailManager->sendGoalDeadlineReminder($goal, $notification);
            $this->em->flush();
            $count++;
        }

        return $count;
    }

    private function createNotification($user, string $type, string $content, ?string $fingerprint = null): WellnessNotification
    {
        $notif = new WellnessNotification();
        $notif->setUser($user);
        $notif->setType($type);
        $notif->setContent($content);
        $notif->setFingerprint($fingerprint);

        $this->em->persist($notif);
        return $notif;
    }
}
