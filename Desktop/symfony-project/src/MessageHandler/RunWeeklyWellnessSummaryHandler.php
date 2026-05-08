<?php

namespace App\MessageHandler;

use App\Message\RunWeeklyWellnessSummary;
use App\Service\WellnessNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RunWeeklyWellnessSummaryHandler
{
    public function __construct(private WellnessNotificationService $service) {}

    public function __invoke(RunWeeklyWellnessSummary $message)
    {
        $this->service->runWeeklyWellnessSummary($message->getUserId());
    }
}
