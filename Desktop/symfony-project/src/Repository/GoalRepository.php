<?php

namespace App\Repository;

use App\Entity\Goal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Goal>
 */
class GoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Goal::class);
    }

    /**
     * Find goals by user, ordered by deadline
     * @return Goal[]
     */
    public function findByUserOrdered($user): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.deadline', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find incomplete goals
     * @return Goal[]
     */
    public function findIncompleteByUser($user): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :user')
            ->andWhere('g.isCompleted = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', false)
            ->orderBy('g.deadline', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find completed goals
     * @return Goal[]
     */
    public function findCompletedByUser($user): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :user')
            ->andWhere('g.isCompleted = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', true)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
