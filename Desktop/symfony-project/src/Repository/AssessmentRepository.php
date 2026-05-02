<?php

namespace App\Repository;

use App\Entity\Assessment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assessment>
 */
class AssessmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assessment::class);
    }

    /**
     * @return Assessment[]
     */
    public function findAllActive(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'Active')
            ->orderBy('a.assessmentId', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Assessment[]
     */
    public function findByType(string $type, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->setParameter('type', $type)
            ->orderBy('a.assessmentId', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}