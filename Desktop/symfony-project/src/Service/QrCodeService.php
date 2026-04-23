<?php

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function generateQrCode(string $data): string
    {
        try {
            $qrCode = new QrCode($data);
            $qrCode->setSize(400);
            $qrCode->setMargin(20);
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            return $result->getDataUri();
        } catch (\Exception $e) {
            return '';
        }
    }
}