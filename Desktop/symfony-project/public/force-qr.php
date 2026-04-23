<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Entity\Session;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$sessions = $em->getRepository(Session::class)->findAll();

foreach ($sessions as $session) {
    $date = $session->getSessionDate() ? $session->getSessionDate()->format('d/m/Y') : 'TBD';
    $startTime = $session->getStartTime() ? $session->getStartTime()->format('H:i') : 'TBD';
    $endTime = $session->getEndTime() ? $session->getEndTime()->format('H:i') : 'TBD';
    
    $textInfo = sprintf(
        "Session: %s\nDate: %s\nHeure: %s-%s\nLieu: %s\nType: %s\nPrix: %s€",
        $session->getTitle(),
        $date,
        $startTime,
        $endTime,
        $session->getLocation(),
        $session->getSessionType(),
        $session->getPrice()
    );
    
    // Générer QR code
    $qrCode = new QrCode($textInfo);
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrCodeDataUri = $result->getDataUri();
    
    $session->setQrCode($qrCodeDataUri);
    echo "Updated session ID: " . $session->getSessionId() . " - " . $session->getTitle() . "\n";
}

$em->flush();
echo "\nDone! " . count($sessions) . " sessions updated.\n";