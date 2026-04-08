<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    /**
     * Find registrations by event
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find registrations by user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->orWhere('r.email = :email')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('r.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count confirmed registrations for an event
     */
    public function countConfirmedByEvent(Event $event): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.event = :event')
            ->andWhere('r.status != :cancelled')
            ->setParameter('event', $event)
            ->setParameter('cancelled', 'CANCELLED')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int)$result : 0;
    }

    /**
     * Get total tickets sold for an event
     */
    public function getTotalTicketsByEvent(Event $event): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.numberOfTickets)')
            ->where('r.event = :event')
            ->andWhere('r.status = :confirmed')
            ->setParameter('event', $event)
            ->setParameter('confirmed', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int)$result : 0;
    }

    /**
     * Get revenue by event
     */
    public function getRevenueByEvent(Event $event): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.totalPrice)')
            ->where('r.event = :event')
            ->andWhere('r.status = :confirmed')
            ->setParameter('event', $event)
            ->setParameter('confirmed', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (float)$result : 0.0;
    }

    /**
     * Get total revenue across all events
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.totalPrice)')
            ->where('r.status = :confirmed')
            ->setParameter('confirmed', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (float)$result : 0.0;
    }

    /**
     * Get total confirmed registrations
     */
    public function getTotalConfirmedCount(): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :confirmed')
            ->setParameter('confirmed', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int)$result : 0;
    }

    /**
     * Check if email is already registered for an event
     */
    public function isEmailRegistered(Event $event, string $email): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.event = :event')
            ->andWhere('r.email = :email')
            ->andWhere('r.status != :cancelled')
            ->setParameter('event', $event)
            ->setParameter('email', $email)
            ->setParameter('cancelled', 'CANCELLED')
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Get registrations by user email
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.email = :email')
            ->setParameter('email', $email)
            ->orderBy('r.registrationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get ticket type distribution
     */
    public function getTicketTypeDistribution(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.ticketType as type', 'COUNT(r.id) as count')
            ->where('r.status = :confirmed')
            ->setParameter('confirmed', 'CONFIRMED')
            ->groupBy('r.ticketType')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent registrations (last 7 days)
     */
    public function findRecentRegistrations(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.registrationDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get registration count by date range
     */
    public function getCountByDateRange(\DateTime $startDate, \DateTime $endDate): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.registrationDate BETWEEN :start AND :end')
            ->andWhere('r.status = :confirmed')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('confirmed', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (int)$result : 0;
    }
}