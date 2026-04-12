<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PDFTicketService
{
    private Environment $twig;
    private QRCodeService $qrCodeService;

    public function __construct(Environment $twig, QRCodeService $qrCodeService)
    {
        $this->twig = $twig;
        $this->qrCodeService = $qrCodeService;
    }

    public function generateTicketContent(EventRegistration $registration, Event $event): string
    {
        $qrCodeDataUri = $this->qrCodeService->getQrCodeBase64($registration, $event);
        
        $html = $this->twig->render('pdf/ticket.html.twig', [
            'registration' => $registration,
            'event' => $event,
            'qrCodeDataUri' => $qrCodeDataUri,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Poppins');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateTicket(EventRegistration $registration, Event $event, ?string $outputPath = null): ?string
    {
        $content = $this->generateTicketContent($registration, $event);
        
        if ($outputPath) {
            file_put_contents($outputPath, $content);
            return $outputPath;
        }
        
        return $content;
    }

    public function generateEventReport(Event $event, array $registrations): string
    {
        $html = $this->twig->render('pdf/event_report.html.twig', [
            'event' => $event,
            'registrations' => $registrations,
            'totalRegistrations' => count($registrations),
            'totalTickets' => array_sum(array_map(fn($r) => $r->getNumberOfTickets(), $registrations)),
            'totalRevenue' => array_sum(array_map(fn($r) => floatval($r->getTotalPrice()), $registrations)),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Poppins');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}