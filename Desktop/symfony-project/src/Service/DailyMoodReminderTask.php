<?php

namespace App\Service;

use App\Entity\WellnessNotification;
use App\Repository\UserRepository;
use App\Repository\MoodRepository;
use App\Repository\WellnessNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class DailyMoodReminderTask
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private MoodRepository $moodRepo,
        private WellnessNotificationRepository $notifRepo,
        private EmailManager $emailManager
    ) {}

    public function run(?int $userId = null): int
    {
        $users = $userId ? [$this->userRepo->find($userId)] : $this->userRepo->findAll();
        $today = new \DateTime('today');
        $count = 0;

        foreach ($users as $user) {
            if (!$user) continue;

            $moodToday = $this->moodRepo->createQueryBuilder('m')
                ->where('m.user = :user')
                ->andWhere('m.createdAt >= :today')
                ->setParameter('user', $user)
                ->setParameter('today', $today)
                ->getQuery()
                ->getResult();

            if (empty($moodToday)) {
                $fingerprint = "daily_mood_" . $user->getId() . "_" . $today->format('Y-m-d');
                
                $existing = $this->notifRepo->findOneBy(['fingerprint' => $fingerprint]);
                if ($existing) continue;

                $notification = $this->createNotification($user, WellnessNotification::TYPE_DAILY_MOOD_REMINDER, "We haven't heard from you today. How are you feeling? Take a moment to log your mood.", $fingerprint);
                $this->emailManager->sendDailyMoodReminder($user, $notification);
                $this->em->flush();
                $count++;
            }
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
