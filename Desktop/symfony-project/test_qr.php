<?php

require_once 'vendor/autoload.php';

use App\Entity\EventRegistration;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get(EntityManagerInterface::class);
$qrService = $container->get(QRCodeService::class);

// Get a registration
$registration = $entityManager->getRepository(EventRegistration::class)->find(1);
$event = $registration->getEvent();

if ($registration && $event) {
    echo "Generating QR for registration ID: " . $registration->getId() . "\n";
    $path = $qrService->generateAndSave($registration, $event);
    echo "QR path: " . $path . "\n";

    $dataUri = $qrService->getQrCodeBase64($registration, $event);
    echo "Data URI length: " . strlen($dataUri) . "\n";
} else {
    echo "Registration or event not found\n";
}