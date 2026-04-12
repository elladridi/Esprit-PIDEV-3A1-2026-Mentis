<?php

namespace App\Repository;

use App\Entity\AssessmentResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AssessmentResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssessmentResult::class);
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.takenAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.takenAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestByUser(int $userId, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.takenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatsByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.resultId) as totalAssessments')
            ->addSelect('AVG(r.totalScore) as averageScore')
            ->addSelect('MAX(r.totalScore) as maxScore')
            ->addSelect('MIN(r.totalScore) as minScore')
            ->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleResult();
    }

    public function countHighRiskResults(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.resultId)')
            ->where('r.riskLevel IN (:highRisks)')
            ->setParameter('highRisks', ['High', 'Severe'])
            ->getQuery()
            ->getSingleScalarResult();
    }
}