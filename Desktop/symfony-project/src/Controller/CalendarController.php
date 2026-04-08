<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/calendar')]
class CalendarController extends AbstractController
{
    private SessionRepository $sessionRepo;

    public function __construct(SessionRepository $sessionRepo)
    {
        $this->sessionRepo = $sessionRepo;
    }

    #[Route('/patient', name: 'calendar_patient', methods: ['GET'])]
    public function patientCalendar(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user is logged in and is a patient
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
                'location' => $session->getLocation(),
                'type' => $session->getSessionType(),
                'status' => $session->getStatus(),
            ];
        }
        
        return $this->render('calendar/patient.html.twig', [
            'events' => json_encode($events),
        ]);
    }

    private function getSessionColor(?string $sessionType): string
    {
        return match (strtolower($sessionType ?? '')) {
            'individual' => '#3498db',
            'group' => '#2ecc71',
            'family' => '#9b59b6',
            'couple' => '#e67e22',
            'online' => '#1abc9c',
            default => '#95a5a6',
        };
    }
}