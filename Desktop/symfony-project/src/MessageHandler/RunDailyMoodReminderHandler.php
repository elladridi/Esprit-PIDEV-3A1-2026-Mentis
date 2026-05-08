<?php

namespace App\MessageHandler;

use App\Message\RunDailyMoodReminder;
use App\Service\WellnessNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RunDailyMoodReminderHandler
{
    public function __construct(private WellnessNotificationService $service) {}

    public function __invoke(RunDailyMoodReminder $message)
    {
        $this->service->runDailyMoodReminder($message->getUserId());
    }
}
