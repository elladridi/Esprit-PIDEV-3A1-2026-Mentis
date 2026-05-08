<?php

namespace App\Service;

use App\Entity\Goal;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoalQrCodeService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $appPublicBaseUrl,
        private string $appSecret
    ) {}

    public function generateQrCode(Goal $goal): string
    {
        $status = $goal->isCompleted() ? 'Completed' : 'Pending';
        $deadline = $goal->getDeadline() ? $goal->getDeadline()->format('Y-m-d H:i') : 'No deadline';
        
        $data = sprintf(
            "MENTIS WELLNESS GOAL\n\nTitle: %s\nDescription: %s\nDeadline: %s\nStatus: %s\nCreated: %s",
            $goal->getTitle(),
            $goal->getDescription(),
            $deadline,
            $status,
            $goal->getCreatedAt()->format('Y-m-d')
        );

        try {
            $qrCode = new QrCode(
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);

            return $result->getDataUri();
        } catch (\Exception $e) {
            return '';
        }
    }
}
