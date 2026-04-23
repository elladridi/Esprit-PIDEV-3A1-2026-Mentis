<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\User;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/calendar')]
class CalendarController extends AbstractController
{
    private SessionRepository $sessionRepo;
    private EntityManagerInterface $em;

    public function __construct(SessionRepository $sessionRepo, EntityManagerInterface $em)
    {
        $this->sessionRepo = $sessionRepo;
        $this->em = $em;
    }

    #[Route('/patient', name: 'calendar_patient', methods: ['GET'])]
    public function patientCalendar(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || $user->getType() !== 'Patient') {
            $this->addFlash('error', 'Only patients can access the calendar.');
            return $this->redirectToRoute('app_home');
        }
        
        $sessions = $this->sessionRepo->findByPatient($user->getId());
        
        $events = [];
        foreach ($sessions as $session) {
            $events[] = [
                'id' => $session->getSessionId(),
                'title' => $session->getTitle(),
                'start' => $session->getSessionDate()->format('Y-m-d') . 'T' . $session->getStartTime()->format('H:i:s'),
                'end' => $session->getSessionDate()->format('Y-m-d') . 'T' . $session->getEndTime()->format('H:i:s'),
                'color' => $this->getSessionColor($session->getSessionType()),
                'url' => $this->generateUrl('session_show', ['id' => $session->getSessionId()]),
            ];
        }
        
        return $this->render('calendar/patient.html.twig', [
            'events' => json_encode($events),
        ]);
    }

    #[Route('/', name: 'calendar_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminCalendar(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/api/events', name: 'calendar_api_events', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getEvents(Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        
        $sessions = $this->sessionRepo->createQueryBuilder('s')
            ->where('s.sessionDate BETWEEN :start AND :end')
            ->setParameter('start', new \DateTime($start))
            ->setParameter('end', new \DateTime($end))
            ->getQuery()
            ->getResult();
        
        $events = [];
        foreach ($sessions as $session) {
            $startDateTime = $session->getSessionDate();
            $startTime = $session->getStartTime();
            $endTime = $session->getEndTime();
            
            if ($startDateTime && $startTime) {
                $events[] = [
                    'id' => $session->getSessionId(),
                    'title' => $session->getTitle(),
                    'start' => $startDateTime->format('Y-m-d') . 'T' . $startTime->format('H:i:s'),
                    'end' => $startDateTime->format('Y-m-d') . 'T' . ($endTime ? $endTime->format('H:i:s') : '23:59:59'),
                    'backgroundColor' => $this->getSessionColor($session->getSessionType()),
                    'borderColor' => $this->getSessionColor($session->getSessionType()),
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'location' => $session->getLocation(),
                        'type' => $session->getSessionType(),
                        'status' => $session->getStatus(),
                        'participants' => $session->getCurrentParticipants() . '/' . $session->getMaxParticipants(),
                        'price' => $session->getPrice(),
                        'description' => $session->getCategory(),
                    ]
                ];
            }
        }
        
        return $this->json($events);
    }

    #[Route('/api/events', name: 'calendar_api_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $session = new Session();
        $session->setTitle($data['title']);
        $session->setSessionDate(new \DateTime($data['date']));
        $session->setLocation($data['location'] ?? 'TBD');
        $session->setSessionType($data['type'] ?? 'Individual');
        $session->setStatus('scheduled');
        $session->setMaxParticipants($data['max_participants'] ?? 20);
        $session->setCurrentParticipants(0);
        $session->setPrice($data['price'] ?? '0.00');
        
        if (isset($data['start_time']) && $data['start_time']) {
            $session->setStartTime(new \DateTime($data['start_time']));
        }
        if (isset($data['end_time']) && $data['end_time']) {
            $session->setEndTime(new \DateTime($data['end_time']));
        }
        
        $this->em->persist($session);
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'id' => $session->getSessionId(),
            'message' => 'Session created successfully!'
        ]);
    }

    #[Route('/api/events/{id}', name: 'calendar_api_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateEvent(int $id, Request $request): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        
        if (!$session) {
            return $this->json(['success' => false, 'message' => 'Session not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Handle drag & drop (date change)
        if (isset($data['start'])) {
            $newDate = new \DateTime($data['start']);
            $session->setSessionDate($newDate);
        }
        
        // Handle full update
        if (isset($data['title'])) {
            $session->setTitle($data['title']);
        }
        if (isset($data['location'])) {
            $session->setLocation($data['location']);
        }
        if (isset($data['type'])) {
            $session->setSessionType($data['type']);
        }
        if (isset($data['max_participants'])) {
            $session->setMaxParticipants($data['max_participants']);
        }
        if (isset($data['price'])) {
            $session->setPrice($data['price']);
        }
        if (isset($data['start_time']) && $data['start_time']) {
            $session->setStartTime(new \DateTime($data['start_time']));
        }
        if (isset($data['end_time']) && $data['end_time']) {
            $session->setEndTime(new \DateTime($data['end_time']));
        }
        
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Session updated successfully!'
        ]);
    }

    #[Route('/api/events/{id}', name: 'calendar_api_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteEvent(int $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        
        if (!$session) {
            return $this->json(['success' => false, 'message' => 'Session not found'], 404);
        }
        
        $this->em->remove($session);
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Session deleted successfully!'
        ]);
    }

    private function getSessionColor(?string $sessionType): string
    {
        return match (strtolower($sessionType ?? '')) {
            'individual' => '#50C878',
            'group' => '#2196F3',
            'family' => '#FF9800',
            'couple' => '#9C27B0',
            'online' => '#00BCD4',
            default => '#757575',
        };
    }
}