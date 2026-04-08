<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    // Get all sessions ordered by date and time
    public function findAllSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.sessionDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Get active sessions only
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

    // Get sessions by type
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

    // Get sessions by date
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.sessionDate = :date')
            ->setParameter('date', $date)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Get sessions by location (search)
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

    // Get available sessions (not reserved by anyone)
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

    // Get sessions reserved by a specific patient
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

    // Get patient's upcoming sessions (today and future)
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

    // Get patient's past sessions
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

    // Search available sessions by keyword (title, location, type)
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

    // Filter available sessions by type
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

    // Update session status
    public function updateSessionStatus(int $sessionId, string $status): bool
    {
        $session = $this->find($sessionId);
        if (!$session) {
            return false;
        }
        
        $session->setStatus($status);
        $this->getEntityManager()->flush();
        return true;
    }

    // Reserve a session for a patient
    public function reserveSession(int $sessionId, int $patientId): bool
    {
        $session = $this->find($sessionId);
        if (!$session || $session->getReservedBy() !== null) {
            return false;
        }
        
        $session->setReservedBy($patientId);
        $session->setReservedAt(new \DateTime());
        $session->incrementPopularity();
        
        $this->getEntityManager()->flush();
        return true;
    }

    // Cancel a reservation
    public function cancelReservation(int $sessionId, int $patientId): bool
    {
        $session = $this->find($sessionId);
        if (!$session || $session->getReservedBy() !== $patientId) {
            return false;
        }
        
        $session->setReservedBy(null);
        $session->setReservedAt(null);
        
        $this->getEntityManager()->flush();
        return true;
    }

    // Check if session is reserved by a specific patient
    public function isReservedByPatient(int $sessionId, int $patientId): bool
    {
        $session = $this->find($sessionId);
        return $session && $session->getReservedBy() === $patientId;
    }

    // Get reservation count for a session
    public function getReservationCount(int $sessionId): int
    {
        $session = $this->find($sessionId);
        return $session && $session->getReservedBy() !== null ? 1 : 0;
    }
}