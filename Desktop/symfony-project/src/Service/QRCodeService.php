<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpKernel\KernelInterface;

class QRCodeService
{
    private string $projectDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
    }

    private function getQrCodeDirectory(): string
    {
        $dir = $this->projectDir . '/public/uploads/qrcodes';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    public function generateContent(EventRegistration $registration, Event $event): string
    {
        return sprintf(
            "===== MENTIS EVENT TICKET =====\n" .
            "Confirmation #: %s\n" .
            "Event: %s\n" .
            "Date: %s\n" .
            "Location: %s\n" .
            "--------------------------------\n" .
            "Attendee: %s\n" .
            "Email: %s\n" .
            "Ticket: %s\n" .
            "Qty: %d\n" .
            "Status: %s\n" .
            "================================",
            $registration->getConfirmationNumber(),
            $event->getTitle(),
            $event->getDateTime()->format('Y-m-d H:i'),
            $event->getLocation(),
            $registration->getUserName(),
            $registration->getEmail(),
            $registration->getTicketType(),
            $registration->getNumberOfTickets(),
            $registration->getStatus()
        );
    }

    public function generateAndSave(EventRegistration $registration, Event $event): ?string
    {
        try {
            $content = $this->generateContent($registration, $event);
            
            // Fix: Use new Builder() instead of Builder::create()
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                data: $content,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );
            
            $result = $builder->build();

            $filename = 'qr_' . $registration->getConfirmationNumber() . '.png';
            $filepath = $this->getQrCodeDirectory() . '/' . $filename;
            
            $result->saveToFile($filepath);
            
            return '/uploads/qrcodes/' . $filename;
            
        } catch (\Exception $e) {
            error_log('QR Code generation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getQrCodeBase64(EventRegistration $registration, Event $event): ?string
    {
        try {
            $content = $this->generateContent($registration, $event);
            
            // Fix: Use new Builder() instead of Builder::create()
            $builder = new Builder(
                writer: new PngWriter(),
                data: $content,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 200,
                margin: 5
            );
            
            $result = $builder->build();

            return $result->getDataUri();
            
        } catch (\Exception $e) {
            error_log('QR Code base64 generation failed: ' . $e->getMessage());
            return null;
        }
    }
}