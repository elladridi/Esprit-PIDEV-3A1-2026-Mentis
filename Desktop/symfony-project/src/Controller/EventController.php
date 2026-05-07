<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRegistrationRepository;
use App\Repository\EventRepository;
use App\Service\AIEventService;
use App\Service\GoogleMapsService;
use App\Service\PDFTicketService;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(
        Request $request,
        EventRepository $eventRepository,
        EventRegistrationRepository $registrationRepository
    ): Response {
        $keyword = $request->query->get('search');
        $type = $request->query->get('type');

        if ($keyword) {
            $events = $eventRepository->search($keyword);
        } elseif ($type && $type !== 'All Types') {
            $events = $eventRepository->findByType($type);
        } else {
            $events = $eventRepository->findBy([], ['dateTime' => 'ASC']);
        }

        $eventStats = [];

        foreach ($events as $event) {
            $eventStats[$event->getId()] = [
                'registrationCount' => $registrationRepository->countConfirmedByEvent($event),
                'revenue' => $registrationRepository->getRevenueByEvent($event),
            ];
        }

        $userRegistrations = [];
        $user = $this->getUser();

        if ($user instanceof User) {
            foreach ($events as $event) {
                $registration = $registrationRepository->findOneBy([
                    'event' => $event,
                    'email' => $user->getEmail(),
                ]);

                if ($registration && $registration->getStatus() !== 'CANCELLED') {
                    $userRegistrations[$event->getId()] = $registration;
                }
            }
        }

        $globalStats = null;

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PSYCHOLOGIST')) {
            $globalStats = [
                'totalEvents' => $eventRepository->count([]),
                'totalParticipants' => $eventRepository->getTotalParticipants(),
                'totalRegistrations' => $registrationRepository->getTotalConfirmedCount(),
                'totalRevenue' => $registrationRepository->getTotalRevenue(),
            ];
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'eventStats' => $eventStats,
            'userRegistrations' => $userRegistrations,
            'globalStats' => $globalStats,
            'searchKeyword' => $keyword,
            'selectedType' => $type,
            'eventTypes' => ['WORKSHOP', 'GROUP_THERAPY', 'SEMINAR', 'SOCIAL'],
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Only administrators and psychologists can create events.');
        }

        $event = new Event();

        $user = $this->getUser();

        if ($user instanceof User) {
            $event->setCreatedBy($user->getId());
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event created successfully!');

            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/form.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'isEdit' => false,
        ]);
    }

    #[Route('/calendar', name: 'app_event_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('event/calendar.html.twig');
    }

    #[Route('/calendar/data', name: 'app_event_calendar_data', methods: ['GET'])]
    public function calendarData(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findBy([], ['dateTime' => 'ASC']);
        $data = [];

        foreach ($events as $event) {
            $color = match ($event->getEventType()) {
                'WORKSHOP' => '#50C878',
                'GROUP_THERAPY' => '#3A9B5E',
                'SEMINAR' => '#2E7D32',
                'SOCIAL' => '#9BC7B5',
                default => '#50C878',
            };

            $borderColor = match ($event->getStatus()) {
                'UPCOMING' => '#2E7D32',
                'ONGOING' => '#FFA726',
                'COMPLETED' => '#9CA3AF',
                'CANCELLED' => '#EF5350',
                default => '#2E7D32',
            };

            $start = $event->getDateTime();
            $end = (clone $start)->modify('+2 hours');

            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'url' => $this->generateUrl('app_event_show', [
                    'id' => $event->getId(),
                ]),
                'backgroundColor' => $color,
                'borderColor' => $borderColor,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => $event->getEventType(),
                    'status' => $event->getStatus(),
                    'location' => $event->getLocation(),
                    'price' => $event->getPrice() > 0 ? '$' . number_format($event->getPrice(), 2) : 'FREE',
                    'currentParticipants' => $event->getCurrentParticipants(),
                    'maxParticipants' => $event->getMaxParticipants(),
                    'availableSpots' => max(0, $event->getMaxParticipants() - $event->getCurrentParticipants()),
                ],
            ];
        }

        return $this->json($data);
    }

   #[Route('/ai/generate-description', name: 'app_event_ai_generate_description', methods: ['POST'])]
public function generateDescription(Request $request, AIEventService $aiEventService): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $title = $data['title'] ?? '';
    $type = $data['type'] ?? '';
    $readableType = $data['readableType'] ?? $type;
    
    if (empty($title)) {
        return $this->json(['success' => false, 'error' => 'Title is required'], 400);
    }
    
    // Use readable type for better AI response
    $eventTypeForAI = !empty($readableType) ? $readableType : $type;
    
    $result = $aiEventService->generateEventDescription($title, $eventTypeForAI);
    
    return $this->json($result);
}

    #[Route('/ai/suggest-ideas', name: 'app_event_ai_suggest_ideas', methods: ['POST'])]
    public function aiSuggestIdeas(Request $request, AIEventService $aiEventService): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            return $this->json([
                'success' => false,
                'error' => 'Access denied.',
            ], 403);
        }

        $topic = $request->request->get('topic', '');
        $audience = $request->request->get('audience', 'patients');

        if (trim($topic) === '') {
            return $this->json([
                'success' => false,
                'ideas' => '',
                'error' => 'Topic is required.',
            ], 400);
        }

        return $this->json(
            $aiEventService->suggestEventIdeas($topic, $audience)
        );
    }

    #[Route('/{id}/ai/performance', name: 'app_event_ai_performance', methods: ['GET'])]
    public function aiPerformance(
        Event $event,
        EventRegistrationRepository $registrationRepository,
        AIEventService $aiEventService
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            return $this->json([
                'success' => false,
                'error' => 'Access denied.',
            ], 403);
        }

        $registrations = $registrationRepository->countConfirmedByEvent($event);
        $revenue = $registrationRepository->getRevenueByEvent($event);

        return $this->json(
            $aiEventService->summarizeEventPerformance($event, $registrations, $revenue)
        );
    }

    #[Route('/qr-test', name: 'qr_test', methods: ['GET'])]
    public function qrTest(QRCodeService $qrCodeService): Response
    {
        try {
            $registration = new \App\Entity\EventRegistration();
            $registration->setConfirmationNumber('TEST-12345');
            $registration->setUserName('Test User');
            $registration->setEmail('test@test.com');
            $registration->setTicketType('STANDARD');
            $registration->setNumberOfTickets(1);
            $registration->setStatus('CONFIRMED');

            $event = new Event();
            $event->setTitle('Test Event');
            $event->setDateTime(new \DateTime());
            $event->setLocation('Test Location');

            $qrCodeBase64 = $qrCodeService->getQrCodeBase64($registration, $event);

            return $this->render('event/qr_test.html.twig', [
                'qrCode' => $qrCodeBase64,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return $this->render('event/qr_test.html.twig', [
                'error' => $e->getMessage(),
                'success' => false,
            ]);
        }
    }

    #[Route('/qr-simple-test', name: 'qr_simple_test', methods: ['GET'])]
    public function qrSimpleTest(): Response
    {
        try {
            $builder = new \Endroid\QrCode\Builder\Builder(
                writer: new \Endroid\QrCode\Writer\PngWriter(),
                data: 'https://mentis.com/test',
                encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
                size: 200,
                margin: 10
            );

            $result = $builder->build();

            return new Response(
                $result->getString(),
                200,
                ['Content-Type' => 'image/png']
            );
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/{id}/report/pdf', name: 'app_event_report_pdf', methods: ['GET'])]
    public function downloadEventReport(
        Event $event,
        EventRegistrationRepository $registrationRepository,
        PDFTicketService $pdfTicketService
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Only administrators and psychologists can export event reports.');
        }

        $registrations = $registrationRepository->findByEvent($event);
        $pdfContent = $pdfTicketService->generateEventReport($event, $registrations);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="event-report-' . $event->getId() . '.pdf"',
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(
        Event $event,
        EventRegistrationRepository $registrationRepository,
        GoogleMapsService $googleMapsService,
        QRCodeService $qrCodeService,
        EntityManagerInterface $entityManager
    ): Response {
        $registrations = $registrationRepository->findByEvent($event);
        $registrationCount = $registrationRepository->countConfirmedByEvent($event);
        $totalTickets = $registrationRepository->getTotalTicketsByEvent($event);
        $revenue = $registrationRepository->getRevenueByEvent($event);

        $userRegistration = null;
        $qrCodeDataUri = null;

        $user = $this->getUser();

        if ($user instanceof User) {
            foreach ($registrations as $registration) {
                if (
                    $registration->getStatus() !== 'CANCELLED' &&
                    (
                        ($registration->getUser() !== null && $registration->getUser()->getId() === $user->getId()) ||
                        strcasecmp($registration->getEmail(), $user->getEmail()) === 0
                    )
                ) {
                    $userRegistration = $registration;
                    $qrCodeDataUri = $qrCodeService->getQrCodeBase64($userRegistration, $event);

                    // Always try to generate and save QR if not already saved
                    if (!$userRegistration->getQrCodePath()) {
                        $qrCodePath = $qrCodeService->generateAndSave($userRegistration, $event);
                        if ($qrCodePath) {
                            $userRegistration->setQrCodePath($qrCodePath);
                            $entityManager->flush();
                        }
                    }
                    break;
                }
            }
        }

        $mapUrl = null;
        $isOnlineEvent = $this->isOnlineEvent($event->getLocation());

        if (!$isOnlineEvent && $event->getLocation() && $googleMapsService->isConfigured()) {
            $mapUrl = $googleMapsService->getStaticMapUrl($event->getLocation());
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'registrations' => $registrations,
            'registrationCount' => $registrationCount,
            'totalTickets' => $totalTickets,
            'revenue' => $revenue,
            'userRegistration' => $userRegistration,
            'qrCodeDataUri' => $qrCodeDataUri,
            'mapUrl' => $mapUrl,
            'isOnlineEvent' => $isOnlineEvent,
            'canManage' => $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PSYCHOLOGIST'),
            'googleMapsService' => $googleMapsService,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Only administrators and psychologists can edit events.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Event updated successfully!');

            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/form.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only administrators can delete events.');
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('app_event_index');
    }

    private function isOnlineEvent(?string $location): bool
    {
        if (!$location) {
            return false;
        }

        $locationLower = strtolower($location);
        $onlineKeywords = ['online', 'virtual', 'zoom', 'teams', 'meet', 'webinar'];

        foreach ($onlineKeywords as $keyword) {
            if (str_contains($locationLower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}