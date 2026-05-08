<?php

namespace App\MessageHandler;

use App\Message\RunGoalDeadlineReminder;
use App\Service\WellnessNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RunGoalDeadlineReminderHandler
{
    public function __construct(private WellnessNotificationService $service) {}

    public function __invoke(RunGoalDeadlineReminder $message)
    {
        $this->service->runGoalDeadlineReminder($message->getUserId());
    }
}
