<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WellnessNotification;
use App\Entity\Goal;
use App\Repository\UserRepository;
use App\Repository\GoalRepository;
use App\Repository\MoodRepository;
use App\Repository\WellnessNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class WellnessNotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private GoalRepository $goalRepo,
        private MoodRepository $moodRepo,
        private WellnessNotificationRepository $notifRepo,
        private EmailManager $emailManager,
        private DailyMoodReminderTask $dailyMoodTask,
        private GoalDeadlineReminderTask $goalDeadlineTask,
        private WeeklyWellnessSummaryTask $weeklySummaryTask
    ) {}

    public function runDailyMoodReminder(?int $userId = null): int
    {
        return $this->dailyMoodTask->run($userId);
    }

    public function runGoalDeadlineReminder(?int $userId = null): int
    {
        return $this->goalDeadlineTask->run($userId);
    }

    public function runWeeklyWellnessSummary(?int $userId = null): int
    {
        return $this->weeklySummaryTask->run($userId);
    }

    private function createNotification(User $user, string $type, string $content, ?string $fingerprint = null, ?array $data = null): void
    {
        $notif = new WellnessNotification();
        $notif->setUser($user);
        $notif->setType($type);
        $notif->setContent($content);
        $notif->setFingerprint($fingerprint);
        $notif->setData($data);

        $this->em->persist($notif);
        $this->em->flush();
    }
}
