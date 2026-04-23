<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\User;
use App\Form\SessionType;
use App\Repository\SessionRepository;
use App\Service\ReminderPopupService;
use App\Service\GeocoderService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/session')]
class SessionController extends AbstractController
{
    private EntityManagerInterface $em;
    private SessionRepository $repo;

    public function __construct(EntityManagerInterface $em, SessionRepository $repo)
    {
        $this->em = $em;
        $this->repo = $repo;
    }

    #[Route('/', name: 'session_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // For patients: redirect to their sessions
        if ($user && $user->getType() === 'Patient') {
            return $this->redirectToRoute('session_by_patient', ['patientId' => $user->getId()]);
        }
        
        // For psychologists: show only their sessions
        if ($this->isGranted('ROLE_PSYCHOLOGIST')) {
            return $this->render('session/index.html.twig', [
                'sessions' => $this->repo->findBy(['reservedBy' => $user->getId()]),
                'isPsychologist' => true,
            ]);
        }
        
        // For admin: show all sessions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('session/index.html.twig', [
            'sessions' => $this->repo->findAllSessions(),
            'isPsychologist' => false,
        ]);
    }

    #[Route('/active', name: 'session_active', methods: ['GET'])]
    public function active(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // For psychologists: show only their active sessions
        if ($this->isGranted('ROLE_PSYCHOLOGIST')) {
            return $this->render('session/active.html.twig', [
                'sessions' => $this->repo->findBy([
                    'reservedBy' => $user->getId(),
                    'status' => 'active'
                ]),
                'isPsychologist' => true,
            ]);
        }
        
        // For admin: show all active sessions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('session/active.html.twig', [
            'sessions' => $this->repo->findActiveSessions(),
            'isPsychologist' => false,
        ]);
    }

    #[Route('/available', name: 'session_available', methods: ['GET'])]
    public function available(): Response
    {
        return $this->render('session/available.html.twig', [
            'sessions' => $this->repo->findAvailableSessions(),
        ]);
    }

    #[Route('/new', name: 'session_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $session = new Session();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($session);
            $this->em->flush();
            $this->addFlash('success', 'Session created successfully!');
            return $this->redirectToRoute('session_index');
        }

        return $this->render('session/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/qr', name: 'session_qr', methods: ['GET'])]
    public function generateQrCode(int $id): Response
    {
        $session = $this->repo->find($id);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        $text = sprintf(
            "Session: %s\nDate: %s\nTime: %s - %s\nLocation: %s\nType: %s\nPrice: %s €\nStatus: %s\nPlaces: %d/%d",
            $session->getTitle(),
            $session->getSessionDate() ? $session->getSessionDate()->format('d/m/Y') : 'TBD',
            $session->getStartTime() ? $session->getStartTime()->format('H:i') : 'TBD',
            $session->getEndTime() ? $session->getEndTime()->format('H:i') : 'TBD',
            $session->getLocation(),
            $session->getSessionType(),
            $session->getPrice(),
            $session->getStatus(),
            $session->getCurrentParticipants(),
            $session->getMaxParticipants()
        );
        
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }

    #[Route('/{id}', name: 'session_show', methods: ['GET'])]
    public function show(int $id, ReminderPopupService $reminderPopupService, GeocoderService $geocoderService): Response
    {
        $session = $this->repo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        // Get coordinates for the session location
        $coordinates = $geocoderService->getSessionLocation($session);
        
        // Get reminder popup data (only for reserved sessions)
        /** @var User $user */
        $user = $this->getUser();
        $reminderData = null;
        
        if ($user && $session->getReservedBy() === $user->getId()) {
            $reminderData = $reminderPopupService->getReminderData($session, new \DateTime());
        }

        return $this->render('session/show.html.twig', [
            'session' => $session,
            'reminderData' => $reminderData,
            'coordinates' => $coordinates,
        ]);
    }

    #[Route('/{id}/edit', name: 'session_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, int $id): Response
    {
        $session = $this->repo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Session updated successfully!');
            return $this->redirectToRoute('session_show', ['id' => $id]);
        }

        return $this->render('session/edit.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'session_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, int $id): Response
    {
        $session = $this->repo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        if ($this->isCsrfTokenValid('delete' . $session->getSessionId(), $request->request->get('_token'))) {
            $this->em->remove($session);
            $this->em->flush();
            $this->addFlash('success', 'Session deleted successfully!');
        }

        return $this->redirectToRoute('session_index');
    }

    #[Route('/{id}/toggle-status', name: 'session_toggle_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleStatus(int $id): Response
    {
        $session = $this->repo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        $statuses = ['scheduled', 'active', 'completed', 'cancelled'];
        $currentIndex = array_search($session->getStatus(), $statuses);
        $nextStatus = $statuses[($currentIndex + 1) % count($statuses)];
        
        $session->setStatus($nextStatus);
        $this->em->flush();

        $this->addFlash('success', 'Status updated to: ' . $nextStatus);
        return $this->redirectToRoute('session_index');
    }

    #[Route('/{id}/reserve', name: 'session_reserve', methods: ['POST'])]
    public function reserve(Request $request, int $id): Response
    {
        $user = $this->getUser();
        
        if (!$user || $user->getType() !== 'Patient') {
            $this->addFlash('error', 'Only patients can reserve sessions.');
            return $this->redirectToRoute('session_available');
        }

        $session = $this->repo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        if ($session->getReservedBy() !== null) {
            $this->addFlash('error', 'This session is already reserved!');
            return $this->redirectToRoute('session_available');
        }

        if (!in_array($session->getStatus(), ['active', 'scheduled'])) {
            $this->addFlash('error', 'This session is not available for reservation.');
            return $this->redirectToRoute('session_available');
        }

        if (!$session->hasAvailableSpots()) {
            $this->addFlash('error', 'No available spots for this session.');
            return $this->redirectToRoute('session_available');
        }

        $session->setReservedBy($user->getId());
        $session->setReservedAt(new \DateTime());
        $session->incrementPopularity();
        $session->setCurrentParticipants($session->getCurrentParticipants() + 1);
        
        $this->em->flush();

        $this->addFlash('success', 'Session reserved successfully!');
        return $this->redirectToRoute('session_by_patient', ['patientId' => $user->getId()]);
    }

    #[Route('/{id}/cancel-reservation', name: 'session_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(Request $request, int $id): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $session = $this->repo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $isOwner = $session->getReservedBy() === $user->getId();
        
        if (!$isOwner && !$isAdmin) {
            $this->addFlash('error', 'You are not authorized to cancel this reservation.');
            return $this->redirectToRoute('session_available');
        }

        if ($session->getReservedBy() === null) {
            $this->addFlash('error', 'This session is not reserved.');
            return $this->redirectToRoute('session_available');
        }

        $session->setReservedBy(null);
        $session->setReservedAt(null);
        $session->setCurrentParticipants(max(0, $session->getCurrentParticipants() - 1));
        
        $this->em->flush();

        $this->addFlash('success', 'Reservation cancelled successfully!');
        
        if ($isAdmin) {
            return $this->redirectToRoute('session_index');
        }
        
        return $this->redirectToRoute('session_by_patient', ['patientId' => $user->getId()]);
    }

    #[Route('/{id}/create-meeting', name: 'session_create_meeting', methods: ['POST'])]
    public function createMeeting(int $id): Response
    {
        $session = $this->repo->find($id);
        $user = $this->getUser();
        
        if (!$session || !$user) {
            $this->addFlash('error', 'Session or user not found');
            return $this->redirectToRoute('session_available');
        }
        
        if ($session->getReservedBy() !== $user->getId()) {
            $this->addFlash('error', 'You can only create meetings for your reserved sessions');
            return $this->redirectToRoute('session_available');
        }
        
        if ($session->getMeetingLink()) {
            $this->addFlash('info', 'A meeting already exists for this session');
            return $this->redirectToRoute('session_show', ['id' => $id]);
        }
        
        $roomName = sprintf(
            "mentis_%s_%d_%d",
            preg_replace('/[^a-zA-Z0-9]/', '_', $session->getTitle()),
            $session->getSessionId(),
            time()
        );
        
        $meetingLink = "https://meet.jit.si/" . $roomName;
        $meetingLink .= "?config.startWithVideoMuted=true&config.startWithAudioMuted=false";
        
        $session->setMeetingLink($meetingLink);
        $session->setMeetingRoomName($roomName);
        $session->setMeetingCreatedAt(new \DateTime());
        $session->setMeetingStarted(true);
        
        $this->em->flush();
        
        $this->addFlash('success', 'Video meeting created successfully!');
        return $this->redirectToRoute('session_show', ['id' => $id]);
    }

    #[Route('/{id}/join-meeting', name: 'session_join_meeting', methods: ['GET'])]
    public function joinMeeting(int $id): Response
    {
        $session = $this->repo->find($id);
        
        if (!$session || !$session->getMeetingLink()) {
            $this->addFlash('error', 'No meeting available for this session');
            return $this->redirectToRoute('session_show', ['id' => $id]);
        }
        
        return $this->redirect($session->getMeetingLink());
    }

    #[Route('/patient/{patientId}', name: 'session_by_patient', methods: ['GET'])]
    public function getByPatient(int $patientId): Response
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }
        
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        $isPsychologist = in_array('ROLE_PSYCHOLOGIST', $currentUser->getRoles());
        $isOwner = $currentUser->getId() === $patientId;
        
        if (!$isOwner && !$isAdmin && !$isPsychologist) {
            $this->addFlash('error', 'You can only view your own sessions.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        return $this->render('session/patient_sessions.html.twig', [
            'upcoming' => $this->repo->findUpcomingByPatient($patientId),
            'past' => $this->repo->findPastByPatient($patientId),
            'patientId' => $patientId,
        ]);
    }

    #[Route('/search/available', name: 'session_search_available', methods: ['GET'])]
    public function searchAvailable(Request $request): Response
    {
        $keyword = $request->query->get('keyword', '');
        return $this->render('session/available.html.twig', [
            'sessions' => $this->repo->searchAvailableSessions($keyword),
            'keyword' => $keyword,
        ]);
    }

    #[Route('/filter/available/{type}', name: 'session_filter_available', methods: ['GET'])]
    public function filterAvailableByType(string $type): Response
    {
        return $this->render('session/available.html.twig', [
            'sessions' => $this->repo->filterAvailableByType($type),
            'filterType' => $type,
        ]);
    }

    #[Route('/search/type', name: 'session_search_type', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function searchByType(Request $request): Response
    {
        $type = $request->query->get('type', '');
        return $this->render('session/index.html.twig', [
            'sessions' => $this->repo->findByType($type),
            'searchType' => $type,
        ]);
    }
}