<?php

namespace App\Scheduler;

use App\Message\RunDailyMoodReminder;
use App\Message\RunGoalDeadlineReminder;
use App\Message\RunWeeklyWellnessSummary;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('wellness')]
class GoalMoodScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('1 day', new RunDailyMoodReminder()),
                RecurringMessage::every('1 day', new RunGoalDeadlineReminder()),
                RecurringMessage::every('7 days', new RunWeeklyWellnessSummary())
            );
    }
}
