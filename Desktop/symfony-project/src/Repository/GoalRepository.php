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

    public function findByUserOrdered($user): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.deadline', 'ASC')
            ->getQuery()
            ->getResult();
    }

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

    public function findFilteredForUser($user, ?string $query, ?string $status, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user);

        if ($query) {
            $qb->andWhere('g.title LIKE :query OR g.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($status === 'completed') {
            $qb->andWhere('g.isCompleted = true');
        } elseif ($status === 'pending') {
            $qb->andWhere('g.isCompleted = false');
        }

        switch ($sort) {
            case 'oldest': $qb->orderBy('g.createdAt', 'ASC'); break;
            case 'deadline_soon': $qb->orderBy('g.deadline', 'ASC'); break;
            case 'deadline_later': $qb->orderBy('g.deadline', 'DESC'); break;
            default: $qb->orderBy('g.createdAt', 'DESC'); break;
        }

        return $qb->getQuery()->getResult();
    }

    // Cette méthode est celle appelée par ton Controller à la ligne 36
    // src/Repository/GoalRepository.php
public function getStatusCountsForUser($user): array
{
    return $this->createQueryBuilder('g')
        ->select('g.status, COUNT(g.id) as count')
        ->where('g.user = :user')
        ->setParameter('user', $user)
        ->groupBy('g.status')
        ->getQuery()
        ->getResult();
}

   public function findCreatedSinceByUser($user, \DateTime $date): array
{
    return $this->createQueryBuilder('e')
        ->where('e.user = :user')
        ->andWhere('e.createdAt >= :date') // Vérifie que ton champ s'appelle bien createdAt
        ->setParameter('user', $user)
        ->setParameter('date', $date)
        ->getQuery()
        ->getResult();
}
}