<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * @return Session[]
     */
    public function findAllSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findActiveSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findByType(string $sessionType): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.sessionType = :type')
            ->setParameter('type', $sessionType)
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.sessionDate = :date')
            ->setParameter('date', $date)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.location LIKE :location')
            ->setParameter('location', '%' . $location . '%')
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findAvailableSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.reservedBy IS NULL')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.reservedBy = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findUpcomingByPatient(int $patientId): array
    {
        $today = new \DateTime();

        return $this->createQueryBuilder('s')
            ->andWhere('s.reservedBy = :patientId')
            ->andWhere('s.sessionDate >= :today')
            ->setParameter('patientId', $patientId)
            ->setParameter('today', $today)
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findPastByPatient(int $patientId): array
    {
        $today = new \DateTime();

        return $this->createQueryBuilder('s')
            ->andWhere('s.reservedBy = :patientId')
            ->andWhere('s.sessionDate < :today')
            ->setParameter('patientId', $patientId)
            ->setParameter('today', $today)
            ->orderBy('s.sessionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function searchAvailableSessions(string $keyword): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.reservedBy IS NULL')
            ->andWhere('s.status = :status')
            ->andWhere('s.title LIKE :keyword OR s.location LIKE :keyword OR s.sessionType LIKE :keyword')
            ->setParameter('status', 'active')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function filterAvailableByType(string $type): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.reservedBy IS NULL')
            ->andWhere('s.status = :status')
            ->andWhere('s.sessionType = :type')
            ->setParameter('status', 'active')
            ->setParameter('type', $type)
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function updateSessionStatus(int $sessionId, string $status): bool
    {
        $session = $this->find($sessionId);

        if (!$session instanceof Session) {
            return false;
        }

        $session->setStatus($status);
        $this->getEntityManager()->flush();

        return true;
    }

    public function reserveSession(int $sessionId, int $patientId): bool
    {
        $session = $this->find($sessionId);

        if (!$session instanceof Session || $session->getReservedBy() !== null) {
            return false;
        }

        $session->setReservedBy($patientId);
        $session->setReservedAt(new \DateTime());
        $session->incrementPopularity();

        $this->getEntityManager()->flush();

        return true;
    }

    public function cancelReservation(int $sessionId, int $patientId): bool
    {
        $session = $this->find($sessionId);

        if (!$session instanceof Session || $session->getReservedBy() !== $patientId) {
            return false;
        }

        $session->setReservedBy(null);
        $session->setReservedAt(null);

        $this->getEntityManager()->flush();

        return true;
    }

    public function isReservedByPatient(int $sessionId, int $patientId): bool
    {
        $session = $this->find($sessionId);

        return $session instanceof Session && $session->getReservedBy() === $patientId;
    }

    public function getReservationCount(int $sessionId): int
    {
        $session = $this->find($sessionId);

        return $session instanceof Session && $session->getReservedBy() !== null ? 1 : 0;
    }
}