<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Goal;
use App\Entity\WellnessNotification;
use App\Repository\UserRepository;
use App\Repository\GoalRepository;
use App\Service\EmailManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:email',
    description: 'Expert tool to test and log wellness emails',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private GoalRepository $goalRepository,
        private EmailManager $emailManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'The type of email (all, mood, goal-completed, goal-approaching, summary)')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the user (default: 20666202@gmail.com)', '20666202@gmail.com')
            ->addOption('log', 'l', InputOption::VALUE_NONE, 'Display recent email logs after sending');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error("User $email not found in database.");
            return Command::FAILURE;
        }

        $io->title("🚀 Mentis Email Testing System - Target: $email");

        switch ($type) {
            case 'mood':
                $this->sendMoodTest($user, $io);
                break;
            case 'goal-completed':
                $this->sendGoalCompletedTest($user, $io);
                break;
            case 'goal-approaching':
                $this->sendGoalApproachingTest($user, $io);
                break;
            case 'summary':
                $this->sendSummaryTest($user, $io);
                break;
            case 'all':
                $this->sendMoodTest($user, $io);
                $this->sendGoalCompletedTest($user, $io);
                $this->sendGoalApproachingTest($user, $io);
                $this->sendSummaryTest($user, $io);
                break;
            default:
                $io->error("Unknown type: $type. Available: all, mood, goal-completed, goal-approaching, summary");
                return Command::FAILURE;
        }

        if ($input->getOption('log')) {
            $this->displayLogs($user, $io);
        }

        return Command::SUCCESS;
    }

    private function sendMoodTest(User $user, SymfonyStyle $io): void
    {
        $io->text("Sending Daily Mood Reminder...");
        $notif = $this->createLog($user, WellnessNotification::TYPE_DAILY_MOOD_REMINDER, "Test: Mood reminder");
        $this->emailManager->sendMoodReminderEmail($user, $notif);
        $this->em->flush();
        $io->success("Mood Reminder sent and logged.");
    }

    private function sendGoalCompletedTest(User $user, SymfonyStyle $io): void
    {
        $io->text("Sending Goal Completed Email...");
        $goal = $this->getOrCreateGoal($user);
        $notif = $this->createLog($user, 'goal_completed', "Test: Goal '{$goal->getTitle()}' completed");
        $this->emailManager->sendGoalCompletedEmail($user, $goal, $notif);
        $this->em->flush();
        $io->success("Goal Completed email sent and logged.");
    }

    private function sendGoalApproachingTest(User $user, SymfonyStyle $io): void
    {
        $io->text("Sending Goal Approaching Email...");
        $goal = $this->getOrCreateGoal($user);
        $notif = $this->createLog($user, WellnessNotification::TYPE_GOAL_DEADLINE_REMINDER, "Test: Goal '{$goal->getTitle()}' due soon");
        $this->emailManager->sendGoalDeadlineReminder($goal, $notif);
        $this->em->flush();
        $io->success("Goal Approaching email sent and logged.");
    }

    private function sendSummaryTest(User $user, SymfonyStyle $io): void
    {
        $io->text("Sending Weekly Summary...");
        $stats = [
            'mood_count' => 7,
            'most_frequent_mood' => 'Calm',
            'completed_goals' => 4,
            'pending_goals' => 2
        ];
        $notif = $this->createLog($user, WellnessNotification::TYPE_WEEKLY_SUMMARY, "Test: Weekly Summary", $stats);
        $this->emailManager->sendWeeklyWellnessSummary($user, $stats, $notif);
        $this->em->flush();
        $io->success("Weekly Summary sent and logged.");
    }

    private function createLog(User $user, string $type, string $content, array $data = []): WellnessNotification
    {
        $notif = new WellnessNotification();
        $notif->setUser($user);
        $notif->setType($type);
        $notif->setContent($content);
        $notif->setData($data);
        $this->em->persist($notif);
        return $notif;
    }

    private function getOrCreateGoal(User $user): Goal
    {
        $goal = $this->goalRepository->findOneBy(['user' => $user]);
        if (!$goal) {
            $goal = new Goal();
            $goal->setTitle("Conquer Your Dreams");
            $goal->setDeadline(new \DateTimeImmutable('+2 days'));
            $goal->setDescription("A test goal for wellness notifications.");
            $goal->setUser($user);
            $this->em->persist($goal);
            $this->em->flush();
        }
        return $goal;
    }

    private function displayLogs(User $user, SymfonyStyle $io): void
    {
        $logs = $this->em->getRepository(WellnessNotification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            5
        );

        $io->section("Latest Email Logs for " . $user->getEmail());
        $tableData = [];
        foreach ($logs as $log) {
            $tableData[] = [
                $log->getCreatedAt()->format('Y-m-d H:i'),
                $log->getType(),
                $log->isEmailSent() ? '✅ Sent' : '❌ Failed',
                $log->getEmailSentAt()?->format('H:i:s') ?? '-',
                $log->getEmailError() ?? 'None'
            ];
        }
        $io->table(['Date', 'Type', 'Status', 'Sent At', 'Error'], $tableData);
    }
}
