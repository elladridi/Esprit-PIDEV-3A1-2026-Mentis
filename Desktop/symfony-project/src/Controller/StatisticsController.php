<?php

namespace App\Controller;

use App\Repository\EventRegistrationRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/statistics')]
class StatisticsController extends AbstractController
{
    #[Route('/', name: 'app_statistics', methods: ['GET'])]
    public function index(
        EventRepository $eventRepository,
        EventRegistrationRepository $registrationRepository
    ): Response {
        // Allow Admin and Psychologist only
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Access denied. Only administrators and psychologists can view statistics.');
        }

        // Summary statistics
        $totalEvents = $eventRepository->count([]);
        $totalParticipants = $eventRepository->getTotalParticipants();
        $totalRegistrations = $registrationRepository->getTotalConfirmedCount();
        $totalRevenue = $registrationRepository->getTotalRevenue();
        $avgTicketPrice = $totalRegistrations > 0 ? $totalRevenue / $totalRegistrations : 0;

        // Events by type
        $eventsByType = $eventRepository->getStatsByType();

        // Revenue by event (only show events with revenue > 0)
        $allEvents = $eventRepository->findAll();
        $revenueByEvent = [];
        foreach ($allEvents as $event) {
            $revenue = $registrationRepository->getRevenueByEvent($event);
            if ($revenue > 0) {
                $revenueByEvent[] = [
                    'title' => $event->getTitle(),
                    'revenue' => $revenue,
                ];
            }
        }

        // Registrations over time (last 30 days using raw SQL for DATE function)
        $conn = $eventRepository->getEntityManager()->getConnection();
        $sql = "SELECT DATE(registration_date) as date, COUNT(*) as count 
                FROM event_registrations 
                WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status = 'CONFIRMED'
                GROUP BY DATE(registration_date) 
                ORDER BY date ASC";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $registrationsOverTime = $result->fetchAllAssociative();

        // Fill missing dates with 0
        $dates = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days")->format('Y-m-d');
            $dates[$date] = 0;
        }
        foreach ($registrationsOverTime as $item) {
            $dates[$item['date']] = (int)$item['count'];
        }
        
        $registrationsOverTimeFormatted = [];
        foreach ($dates as $date => $count) {
            $registrationsOverTimeFormatted[] = [
                'date' => (new \DateTime($date))->format('m/d'),
                'count' => $count,
            ];
        }

        // Ticket type distribution
        $ticketTypeDistribution = $registrationRepository->getTicketTypeDistribution();

        // Status distribution
        $confirmedCount = $registrationRepository->getTotalConfirmedCount();
        $pendingCount = $registrationRepository->count(['status' => 'PENDING']);
        $cancelledCount = $registrationRepository->count(['status' => 'CANCELLED']);

        return $this->render('event/statistics.html.twig', [
            'summary' => [
                'totalEvents' => $totalEvents,
                'totalParticipants' => $totalParticipants,
                'totalRegistrations' => $totalRegistrations,
                'totalRevenue' => $totalRevenue,
                'avgTicketPrice' => $avgTicketPrice,
            ],
            'eventsByType' => $eventsByType,
            'revenueByEvent' => $revenueByEvent,
            'registrationsOverTime' => $registrationsOverTimeFormatted,
            'ticketTypeDistribution' => $ticketTypeDistribution,
            'confirmedCount' => $confirmedCount,
            'pendingCount' => $pendingCount,
            'cancelledCount' => $cancelledCount,
        ]);
    }
}