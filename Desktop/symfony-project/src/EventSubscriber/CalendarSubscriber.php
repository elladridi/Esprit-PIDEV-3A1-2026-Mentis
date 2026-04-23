<?php

namespace App\EventSubscriber;

use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use App\Repository\SessionRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private SessionRepository $sessionRepo;

    public function __construct(SessionRepository $sessionRepo)
    {
        $this->sessionRepo = $sessionRepo;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent): void
    {
        $start = $setDataEvent->getStart();
        $end = $setDataEvent->getEnd();
        
        // Get sessions between start and end dates
        $sessions = $this->sessionRepo->createQueryBuilder('s')
            ->where('s.sessionDate BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        foreach ($sessions as $session) {
            $startDateTime = $session->getSessionDate();
            $endDateTime = $session->getEndTime() ?? clone $session->getSessionDate();
            
            $event = new Event(
                $session->getTitle(),
                $startDateTime,
                $endDateTime
            );
            
            // Customize event appearance
            $event->setBgColor($this->getSessionColor($session->getSessionType()));
            $event->setFgColor('#ffffff');
            
            // Add custom data for event click
            $event->setOptions([
                'location' => $session->getLocation(),
                'type' => $session->getSessionType(),
                'status' => $session->getStatus(),
                'participants' => $session->getCurrentParticipants() . '/' . $session->getMaxParticipants(),
                'price' => $session->getPrice(),
                'id' => $session->getSessionId(),
            ]);
            
            $setDataEvent->addEvent($event);
        }
    }
    
    private function getSessionColor(string $type): string
    {
        return match ($type) {
            'Individual' => '#50C878',  // Green
            'Group' => '#2196F3',       // Blue
            'Family' => '#FF9800',      // Orange
            'Couple' => '#9C27B0',      // Purple
            'Online' => '#00BCD4',      // Cyan
            default => '#757575',        // Grey
        };
    }
}