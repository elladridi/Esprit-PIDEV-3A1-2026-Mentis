<?php

namespace App\Service;

use App\Entity\Goal;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailManager
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderEmail,
        private readonly string $appPublicBaseUrl,
    ) {
    }

    public function sendGoalCompletedEmail(User $user, Goal $goal, ?\App\Entity\WellnessNotification $notification = null): void
    {
        try {
            $subject = sprintf('Goal completed: %s', (string)$goal->getTitle());
            $deadline = $goal->getDeadline();
            $deadlineText = $deadline ? $deadline->format('F j, Y \a\t H:i') : 'No deadline';

            $goalTitle = htmlspecialchars((string)$goal->getTitle(), ENT_QUOTES, 'UTF-8');
            $deadlineEscaped = htmlspecialchars($deadlineText, ENT_QUOTES, 'UTF-8');
            $goalsUrl = htmlspecialchars($this->absoluteUrl('/goal/'), ENT_QUOTES, 'UTF-8');

            $html = sprintf(
                '<!DOCTYPE html>
                <html lang="en">
                <head><meta charset="UTF-8"><title>Goal Completed</title></head>
                <body style="margin:0; padding:0; background-color:#f4f8f5; font-family:Arial, sans-serif; color:#1f2937;">
                    <table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="padding:30px 0;">
                        <tr>
                            <td align="center">
                                <table role="presentation" width="100%%" style="max-width:620px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,0.08);">
                                    <tr><td style="background:linear-gradient(135deg, #50C878 0%%, #2E9E5B 100%%); padding:32px 24px; text-align:center;"><h1 style="margin:0; font-size:32px; color:#ffffff;">Goal Completed</h1></td></tr>
                                    <tr><td style="padding:36px 32px;"><p style="font-size:18px;">Hello,</p><p>Congratulations! You have successfully completed: <strong>%s</strong></p><p>Deadline was: %s</p><div style="text-align:center; margin:30px 0;"><a href="%s" style="background:#50C878; color:#ffffff; padding:14px 26px; text-decoration:none; border-radius:10px; font-weight:bold;">View My Goals</a></div></td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>',
                $goalTitle, $deadlineEscaped, $goalsUrl
            );

            $this->sendToUser($user, $subject, $html);
            $this->logNotificationSuccess($notification);
        } catch (\Exception $e) {
            $this->logNotificationError($notification, $e->getMessage());
            throw $e;
        }
    }

    public function sendMoodReminderEmail(User $user, ?\App\Entity\WellnessNotification $notification = null): void
    {
        try {
            $firstName = htmlspecialchars((string)$user->getFirstname(), ENT_QUOTES, 'UTF-8');
            $moodUrl = htmlspecialchars($this->absoluteUrl('/mood/'), ENT_QUOTES, 'UTF-8');
            $greetingName = $firstName !== '' ? $firstName : 'there';

            $content = sprintf(
                '<p>Hi %s, we noticed you have not logged a mood entry today. A quick check-in helps Mentis keep your emotional timeline complete.</p>
                <div style="text-align:center; margin:30px 0;"><a href="%s" style="background:#14b8a6; color:#ffffff; padding:15px 28px; text-decoration:none; border-radius:14px; font-weight:bold;">Log today\'s mood</a></div>',
                $greetingName, $moodUrl
            );

            $html = $this->buildWellnessEmailLayout('Daily Mood Reminder', 'A gentle prompt to reflect on your day.', 'linear-gradient(135deg, #14b8a6 0%%, #0f766e 100%%)', 'Daily Wellness', $content);
            $this->sendToUser($user, 'Daily mood check-in reminder', $html);
            $this->logNotificationSuccess($notification);
        } catch (\Exception $e) {
            $this->logNotificationError($notification, $e->getMessage());
            throw $e;
        }
    }

    public function sendDailyMoodReminder(User $user, ?\App\Entity\WellnessNotification $notification = null): void
    {
        $this->sendMoodReminderEmail($user, $notification);
    }

    public function sendGoalDeadlineReminderEmail(User $user, Goal $goal, ?\App\Entity\WellnessNotification $notification = null): void
    {
        try {
            $goalTitle = htmlspecialchars((string)$goal->getTitle(), ENT_QUOTES, 'UTF-8');
            $goalUrl = htmlspecialchars($this->absoluteUrl('/goal/'), ENT_QUOTES, 'UTF-8');
            $deadlineText = htmlspecialchars($goal->getDeadline()?->format('F j, Y \a\t H:i') ?? 'Soon', ENT_QUOTES, 'UTF-8');

            $content = sprintf(
                '<p>Hi %s, your goal <strong>%s</strong> is approaching its deadline (%s).</p>
                <div style="text-align:center; margin:30px 0;"><a href="%s" style="background:#2563eb; color:#ffffff; padding:15px 28px; text-decoration:none; border-radius:14px; font-weight:bold;">Review this goal</a></div>',
                htmlspecialchars((string)$user->getFirstname(), ENT_QUOTES, 'UTF-8'), $goalTitle, $deadlineText, $goalUrl
            );

            $html = $this->buildWellnessEmailLayout('Goal Deadline Reminder', 'Stay on track with your progress.', 'linear-gradient(135deg, #2563eb 0%%, #4f46e5 100%%)', 'Goal Reminder', $content);
            $this->sendToUser($user, 'Upcoming goal deadline: ' . (string)$goal->getTitle(), $html);
            $this->logNotificationSuccess($notification);
        } catch (\Exception $e) {
            $this->logNotificationError($notification, $e->getMessage());
            throw $e;
        }
    }

    public function sendGoalApproachingEmail(User $user, Goal $goal, ?\App\Entity\WellnessNotification $notification = null): void
    {
        $this->sendGoalDeadlineReminderEmail($user, $goal, $notification);
    }

    public function sendGoalDeadlineReminder(Goal $goal, ?\App\Entity\WellnessNotification $notification = null): void
    {
        $this->sendGoalDeadlineReminderEmail($goal->getUser(), $goal, $notification);
    }

    public function sendWeeklyWellnessSummaryEmail(User $user, array $summary, ?\App\Entity\WellnessNotification $notification = null): void
    {
        try {
            $remindersUrl = htmlspecialchars($this->absoluteUrl('/dashboard/reminders'), ENT_QUOTES, 'UTF-8');
            $moodEntries = (int)($summary['mood_count'] ?? 0);
            $topMood = htmlspecialchars((string)($summary['most_frequent_mood'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
            $completedGoals = (int)($summary['completed_goals'] ?? 0);
            $pendingGoals = (int)($summary['pending_goals'] ?? 0);

            $content = sprintf(
                '<p>Hi %s, here is your weekly snapshot:</p>
                <ul><li>Moods: %d</li><li>Top Mood: %s</li><li>Goals Completed: %d</li><li>Pending: %d</li></ul>
                <div style="text-align:center; margin:30px 0;"><a href="%s" style="background:#7c3aed; color:#ffffff; padding:15px 28px; text-decoration:none; border-radius:14px; font-weight:bold;">View Reminders</a></div>',
                htmlspecialchars((string)$user->getFirstname(), ENT_QUOTES, 'UTF-8'), $moodEntries, $topMood, $completedGoals, $pendingGoals, $remindersUrl
            );

            $html = $this->buildWellnessEmailLayout('Weekly Wellness Summary', 'Your weekly recap.', 'linear-gradient(135deg, #7c3aed 0%%, #4f46e5 100%%)', 'Weekly Summary', $content);
            $this->sendToUser($user, 'Your weekly Mentis wellness summary', $html);
            $this->logNotificationSuccess($notification);
        } catch (\Exception $e) {
            $this->logNotificationError($notification, $e->getMessage());
            throw $e;
        }
    }

    public function sendWeeklyWellnessSummary(User $user, array $summary, ?\App\Entity\WellnessNotification $notification = null): void
    {
        $this->sendWeeklyWellnessSummaryEmail($user, $summary, $notification);
    }

    private function logNotificationSuccess(?\App\Entity\WellnessNotification $notification): void
    {
        if ($notification) {
            $notification->setEmailSent(true);
            $notification->setEmailSentAt(new \DateTimeImmutable());
        }
    }

    private function logNotificationError(?\App\Entity\WellnessNotification $notification, string $error): void
    {
        if ($notification) {
            $notification->setEmailSent(false);
            $notification->setEmailError($error);
        }
    }

    private function sendToUser(User $user, string $subject, string $html): void
    {
        $emailAddress = 'ahmedzekri20666202@gmail.com';
        $email = (new Email())
            ->from(new Address($this->senderEmail, 'Mentis'))
            ->to($emailAddress)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim($this->appPublicBaseUrl, '/') . $path;
    }

    private function buildWellnessEmailLayout(string $title, string $subtitle, string $heroGradient, string $eyebrow, string $content): string 
    {
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $subtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
        $eyebrow = htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<!DOCTYPE html>
            <html lang="en">
            <head><meta charset="UTF-8"><title>%s</title></head>
            <body style="margin:0; padding:0; background-color:#eef4f7; font-family:Arial, sans-serif;">
                <table width="100%%" style="max-width:640px; margin:20px auto; background:#ffffff; border-radius:28px; overflow:hidden;">
                    <tr><td style="background:%s; padding:36px; color:#ffffff;">
                        <div style="font-size:12px; font-weight:800; text-transform:uppercase;">%s</div>
                        <h1 style="margin:10px 0;">%s</h1>
                        <p>%s</p>
                    </td></tr>
                    <tr><td style="padding:34px;">%s</td></tr>
                </table>
            </body></html>',
            $title, $heroGradient, $eyebrow, $title, $subtitle, $content
        );
        }
}
