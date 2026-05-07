<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventRegistration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PDFTicketService
{
    public function __construct(
        private Environment $twig,
        private QRCodeService $qrCodeService
    ) {
    }

    public function generateTicketContent(EventRegistration $registration, Event $event): string
    {
        $qrCodeDataUri = $this->qrCodeService->getQrCodeBase64($registration, $event);

        $html = $this->twig->render('pdf/ticket.html.twig', [
            'registration' => $registration,
            'event' => $event,
            'qrCodeDataUri' => $qrCodeDataUri,
        ]);

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateTicket(
        EventRegistration $registration,
        Event $event,
        ?string $outputPath = null
    ): string {
        $content = $this->generateTicketContent($registration, $event);

        if ($outputPath !== null) {
            file_put_contents($outputPath, $content);

            return $outputPath;
        }

        return $content;
    }

    public function generateEventReport(Event $event, array $registrations): string
    {
        $totalRegistrations = count($registrations);

        $totalTickets = array_sum(array_map(
            fn (EventRegistration $registration) => $registration->getNumberOfTickets(),
            $registrations
        ));

        $totalRevenue = array_sum(array_map(
            fn (EventRegistration $registration) => (float) $registration->getTotalPrice(),
            $registrations
        ));

        $html = $this->twig->render('pdf/event_report.html.twig', [
            'event' => $event,
            'registrations' => $registrations,
            'totalRegistrations' => $totalRegistrations,
            'totalTickets' => $totalTickets,
            'totalRevenue' => $totalRevenue,
        ]);

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    private function createDompdf(): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', false);

        return new Dompdf($options);
    }
}