<?php
// src/Service/BanNotificationService.php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class BanNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $senderEmail
    ) {}

    public function sendBanNotification(User $user, string $reason, \DateTime $bannedUntil): void
    {
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($user->getEmail())
            ->subject('⚠️ Your Mentis Account Has Been Suspended')
            ->html($this->buildBanEmailHtml($user, $reason, $bannedUntil));

        $this->mailer->send($email);
    }

    public function sendUnbanNotification(User $user): void
    {
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($user->getEmail())
            ->subject('✅ Your Mentis Account Has Been Reinstated')
            ->html($this->buildUnbanEmailHtml($user));

        $this->mailer->send($email);
    }

    private function buildBanEmailHtml(User $user, string $reason, \DateTime $bannedUntil): string
    {
        $name      = htmlspecialchars($user->getFullName());
        $until     = $bannedUntil->format('F j, Y \a\t H:i');
        $reasonEsc = htmlspecialchars($reason);

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #e53e3e, #c53030); padding: 30px; text-align: center; }
                .header h1 { color: white; margin: 0; font-size: 24px; }
                .header .icon { font-size: 48px; margin-bottom: 10px; }
                .body { padding: 30px; color: #2d3748; }
                .body h2 { color: #2d3748; margin-top: 0; }
                .info-box { background: #fff5f5; border-left: 4px solid #e53e3e; border-radius: 6px; padding: 15px 20px; margin: 20px 0; }
                .info-box p { margin: 6px 0; font-size: 14px; }
                .info-box strong { color: #c53030; }
                .until-box { background: #f7fafc; border-radius: 8px; padding: 15px 20px; text-align: center; margin: 20px 0; }
                .until-box .date { font-size: 20px; font-weight: bold; color: #2d3748; margin-top: 5px; }
                .footer { background: #f7fafc; padding: 20px 30px; text-align: center; color: #718096; font-size: 13px; border-top: 1px solid #e2e8f0; }
                .footer a { color: #50C878; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="icon">🚫</div>
                    <h1>Account Suspended</h1>
                </div>
                <div class="body">
                    <h2>Hello, {$name}</h2>
                    <p>We are writing to inform you that your <strong>Mentis</strong> account has been temporarily suspended by our security team.</p>

                    <div class="info-box">
                        <p><strong>Reason:</strong> {$reasonEsc}</p>
                        <p><strong>Duration:</strong> 7 days</p>
                    </div>

                    <div class="until-box">
                        <p style="margin:0; color:#718096; font-size:14px;">Your account will be automatically reinstated on</p>
                        <div class="date">📅 {$until}</div>
                    </div>

                    <p>During this period you will not be able to log in to your account. If you believe this suspension was made in error, please contact our support team.</p>

                    <p>To appeal this decision, reply to this email or contact us at <a href="mailto:support@mentis.com">support@mentis.com</a>.</p>
                </div>
                <div class="footer">
                    <p>© 2026 Mentis Mental Health Platform</p>
                    <p>This is an automated security notification. Please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildUnbanEmailHtml(User $user): string
    {
        $name = htmlspecialchars($user->getFullName());

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #50C878, #3A9B5E); padding: 30px; text-align: center; }
                .header h1 { color: white; margin: 0; font-size: 24px; }
                .body { padding: 30px; color: #2d3748; }
                .cta { display: block; background: linear-gradient(135deg, #50C878, #3A9B5E); color: white; text-decoration: none; text-align: center; padding: 14px 30px; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .footer { background: #f7fafc; padding: 20px 30px; text-align: center; color: #718096; font-size: 13px; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div style="font-size:48px; margin-bottom:10px;">✅</div>
                    <h1>Account Reinstated</h1>
                </div>
                <div class="body">
                    <h2>Hello, {$name}</h2>
                    <p>Great news! Your <strong>Mentis</strong> account suspension has been lifted and you can now log in again.</p>
                    <p>We encourage you to review our <a href="#" style="color:#50C878;">community guidelines</a> to ensure continued access to the platform.</p>
                    <a href="/login" class="cta">🔑 Log In to Your Account</a>
                    <p style="color:#718096; font-size:13px;">If you have any questions, contact us at <a href="mailto:support@mentis.com" style="color:#50C878;">support@mentis.com</a></p>
                </div>
                <div class="footer">
                    <p>© 2026 Mentis Mental Health Platform</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}