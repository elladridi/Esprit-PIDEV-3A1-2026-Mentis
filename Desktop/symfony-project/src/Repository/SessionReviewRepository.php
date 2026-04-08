<?php

namespace App\Repository;

use App\Entity\SessionReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionReview::class);
    }

    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.patientId = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('r.reviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySession(int $sessionId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.sessionId = :sessionId')
            ->orderBy('r.reviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasPatientReviewed(int $sessionId, int $patientId): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.reviewId)')
            ->andWhere('r.sessionId = :sessionId')
            ->andWhere('r.patientId = :patientId')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('patientId', $patientId)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result > 0;
    }

    public function getAverageRating(int $sessionId): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->andWhere('r.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (float)$result : 0.0;
    }

    public function getReviewCount(int $sessionId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.reviewId)')
            ->andWhere('r.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.reviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}