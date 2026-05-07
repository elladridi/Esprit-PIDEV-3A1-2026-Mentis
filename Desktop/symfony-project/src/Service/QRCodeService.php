<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QRCodeService
{
    private string $projectDir;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(KernelInterface $kernel, UrlGeneratorInterface $urlGenerator)
    {
        $this->projectDir = $kernel->getProjectDir();
        $this->urlGenerator = $urlGenerator;
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
        $ticketUrl = $this->urlGenerator->generate('app_registration_download_ticket', [
            'id' => $registration->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $ticketUrl;
    }

    public function generateAndSave(EventRegistration $registration, Event $event): ?string
    {
        try {
            $content = $this->generateContent($registration, $event);
            
            // CORRECT SYNTAX FOR VERSION 6 - using new Builder() directly
            $builder = new Builder(
                writer: new PngWriter(),
                data: $content,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10
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
            
            // CORRECT SYNTAX FOR VERSION 6 - using new Builder() directly
            $builder = new Builder(
                writer: new PngWriter(),
                data: $content,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 200,
                margin: 10
            );
            
            $result = $builder->build();

            return $result->getDataUri();
            
        } catch (\Exception $e) {
            error_log('QR Code base64 generation failed: ' . $e->getMessage());
            return null;
        }
    }
}