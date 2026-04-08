<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRegistrationRepository;
use App\Repository\EventRepository;
use App\Service\GoogleMapsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        // Apply filters
        if ($keyword) {
            $events = $eventRepository->search($keyword);
        } elseif ($type && $type !== 'All Types') {
            $events = $eventRepository->findByType($type);
        } else {
            $events = $eventRepository->findBy([], ['dateTime' => 'ASC']);
        }

        // Calculate stats for each event
        $eventStats = [];
        foreach ($events as $event) {
            $eventStats[$event->getId()] = [
                'registrationCount' => $registrationRepository->countConfirmedByEvent($event),
                'revenue' => $registrationRepository->getRevenueByEvent($event),
            ];
        }

        // Global stats for admin/psychologist
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
            'globalStats' => $globalStats,
            'searchKeyword' => $keyword,
            'selectedType' => $type,
            'eventTypes' => ['WORKSHOP', 'GROUP_THERAPY', 'SEMINAR', 'SOCIAL'],
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // FIXED: Allow Admin OR Psychologist to create events
        // Changed from 'or' to '&&' for correct logic
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

   #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
public function show(
    Event $event,
    EventRegistrationRepository $registrationRepository,
    GoogleMapsService $googleMapsService  // Make sure this is injected
): Response {
    $registrations = $registrationRepository->findByEvent($event);
    $registrationCount = $registrationRepository->countConfirmedByEvent($event);
    $totalTickets = $registrationRepository->getTotalTicketsByEvent($event);
    $revenue = $registrationRepository->getRevenueByEvent($event);

    // Check if current user is registered (for patients)
    $userRegistration = null;
    if ($this->getUser() && $this->isGranted('ROLE_PATIENT')) {
        foreach ($registrations as $reg) {
            if ($reg->getEmail() === $this->getUser()->getEmail()) {
                $userRegistration = $reg;
                break;
            }
        }
    }

    // Get map URL
    $mapUrl = null;
    $isOnlineEvent = $this->isOnlineEvent($event->getLocation());
    if (!$isOnlineEvent && $event->getLocation()) {
        $mapUrl = $googleMapsService->getStaticMapUrl($event->getLocation());
    }

    return $this->render('event/show.html.twig', [
        'event' => $event,
        'registrations' => $registrations,
        'registrationCount' => $registrationCount,
        'totalTickets' => $totalTickets,
        'revenue' => $revenue,
        'userRegistration' => $userRegistration,
        'mapUrl' => $mapUrl,
        'isOnlineEvent' => $isOnlineEvent,
        'canManage' => $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PSYCHOLOGIST'),
        'googleMapsService' => $googleMapsService,  // ADD THIS LINE
    ]);
}

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        // FIXED: Allow Admin OR Psychologist to edit events
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
        // Only Admin can delete events (NOT Psychologist)
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
        if (!$location) return false;
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