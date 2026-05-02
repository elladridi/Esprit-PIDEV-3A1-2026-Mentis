<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\Result\ResultInterface;

class QRCodeService
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function generateAndSave(EventRegistration $registration, Event $event): ?string
    {
        $result = $this->buildQrCode($registration, $event);

        if (null === $result) {
            return null;
        }

        $directory = $this->projectDir . '/public/uploads/qrcodes';
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            return null;
        }

        $fileName = sprintf(
            'registration_%s_%s.png',
            $registration->getId() ?? 'new',
            preg_replace('/[^A-Za-z0-9_-]/', '_', $registration->getConfirmationNumber() ?? uniqid('qr_', true))
        );

        $absolutePath = $directory . '/' . $fileName;
        file_put_contents($absolutePath, $result->getString());

        return '/uploads/qrcodes/' . $fileName;
    }

    public function getQrCodeBase64(EventRegistration $registration, Event $event): ?string
    {
        $result = $this->buildQrCode($registration, $event);

        return $result?->getDataUri();
    }

    private function buildQrCode(EventRegistration $registration, Event $event): ?ResultInterface
    {
        $payload = [
            'registration_id' => $registration->getId(),
            'confirmation_number' => $registration->getConfirmationNumber(),
            'event_id' => $event->getId(),
            'event_title' => $event->getTitle(),
            'attendee' => $registration->getUserName(),
            'email' => $registration->getEmail(),
            'tickets' => $registration->getNumberOfTickets(),
        ];

        try {
            return (new Builder())->build(
                data: json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 300,
                margin: 10
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
