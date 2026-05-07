<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Search events by keyword in title or description.
     *
     * @return Event[]
     */
    public function search(string $keyword): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.title LIKE :keyword')
            ->orWhere('e.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBetweenDates(\DateTimeInterface $start, \DateTimeInterface $end)
{
    return $this->createQueryBuilder('e')
        ->andWhere('e.dateTime BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getResult();
}

    /**
     * Find events by type.
     *
     * @return Event[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.eventType = :type')
            ->setParameter('type', $type)
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming events.
     *
     * @return Event[]
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.dateTime > :now')
            ->setParameter('status', 'UPCOMING')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ongoing events.
     *
     * @return Event[]
     */
    public function findOngoing(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', 'ONGOING')
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find completed events.
     *
     * @return Event[]
     */
    public function findCompleted(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', 'COMPLETED')
            ->orderBy('e.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total participants across all events.
     */
    public function getTotalParticipants(): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.currentParticipants)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get statistics by event type.
     *
     * @return array<int, array{
     *     type: string|null,
     *     count: int|string
     * }>
     */
    public function getStatsByType(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.eventType as type', 'COUNT(e.id) as count')
            ->groupBy('e.eventType')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get events with their registration counts.
     *
     * @return array<int, array{0: Event, registrationCount: int|string}>
     */
    public function findWithRegistrationCounts(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e', 'COUNT(r.id) as registrationCount')
            ->leftJoin('e.registrations', 'r')
            ->groupBy('e.id')
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get upcoming events with available spots.
     *
     * @return Event[]
     */
    public function findUpcomingWithAvailability(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.currentParticipants < e.maxParticipants')
            ->andWhere('e.dateTime > :now')
            ->setParameter('status', 'UPCOMING')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}