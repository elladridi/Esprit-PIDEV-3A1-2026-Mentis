<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailNotificationService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private QRCodeService $qrCodeService;

    public function __construct(MailerInterface $mailer, string $senderEmail, QRCodeService $qrCodeService)
    {
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
        $this->qrCodeService = $qrCodeService;
    }

    public function sendConfirmationEmail(EventRegistration $registration, Event $event): bool
    {
        try {
            // Generate QR code as base64 for email
            $qrCodeDataUri = $this->qrCodeService->getQrCodeBase64($registration, $event);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, 'MENTIS Events'))
                ->to($registration->getEmail())
                ->subject('🎟 Registration Confirmed: ' . $event->getTitle())
                ->htmlTemplate('email/registration_confirmation.html.twig')
                ->context([
                    'registration' => $registration,
                    'event' => $event,
                    'confirmationNumber' => $registration->getConfirmationNumber(),
                    'qrCodeDataUri' => $qrCodeDataUri,
                ]);

            $this->mailer->send($email);
            return true;
            
        } catch (\Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendCancellationEmail(EventRegistration $registration, Event $event): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, 'MENTIS Events'))
                ->to($registration->getEmail())
                ->subject('❌ Registration Cancelled: ' . $event->getTitle())
                ->htmlTemplate('email/registration_canceled.html.twig')
                ->context([
                    'registration' => $registration,
                    'event' => $event,
                ]);

            $this->mailer->send($email);
            return true;
            
        } catch (\Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
