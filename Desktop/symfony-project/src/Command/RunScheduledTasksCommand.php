<?php

namespace App\Command;

use App\Message\RunDailyMoodReminder;
use App\Message\RunGoalDeadlineReminder;
use App\Message\RunWeeklyWellnessSummary;
use App\Service\DailyMoodReminderTask;
use App\Service\GoalDeadlineReminderTask;
use App\Service\WeeklyWellnessSummaryTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:wellness:run-scheduled-tasks',
    description: 'Manually run wellness scheduled tasks',
)]
class RunScheduledTasksCommand extends Command
{
    public function __construct(
        private MessageBusInterface $bus,
        private DailyMoodReminderTask $dailyMoodTask,
        private GoalDeadlineReminderTask $goalDeadlineTask,
        private WeeklyWellnessSummaryTask $weeklySummaryTask
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('task', InputArgument::OPTIONAL, 'The task to run (all, daily-mood, goal-deadline, weekly-summary)', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $taskName = $input->getArgument('task');

        switch ($taskName) {
            case 'daily-mood':
                $count = $this->dailyMoodTask->run();
                $io->success("daily-mood: $count notification(s) created.");
                break;
            case 'goal-deadline':
                $count = $this->goalDeadlineTask->run();
                $io->success("goal-deadline: $count notification(s) created.");
                break;
            case 'weekly-summary':
                $count = $this->weeklySummaryTask->run();
                $io->success("weekly-summary: $count notification(s) created.");
                break;
            case 'all':
            default:
                $c1 = $this->dailyMoodTask->run();
                $c2 = $this->goalDeadlineTask->run();
                $c3 = $this->weeklySummaryTask->run();
                $io->success("All tasks completed. Daily: $c1, Goals: $c2, Weekly: $c3 notification(s) created.");
                break;
        }

        return Command::SUCCESS;
    }
}
