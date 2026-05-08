<?php

namespace App\AnalyticsBundle\Repository;

use App\Entity\Goal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * getStatusCountsForUser(UserInterface $user) : Retourne un tableau associatif [statut => nombre]
     */
    public function getStatusCountsForUser(UserInterface $user): array
    {
        $results = $this->createQueryBuilder('g')
            ->select('g.isCompleted, COUNT(g.id) as count')
            ->where('g.user = :user')
            ->setParameter('user', $user)
            ->groupBy('g.isCompleted')
            ->getQuery()
            ->getResult();

        $stats = ['completed' => 0, 'pending' => 0];
        foreach ($results as $result) {
            $key = $result['isCompleted'] ? 'completed' : 'pending';
            $stats[$key] = (int) $result['count'];
        }

        return $stats;
    }

    /**
     * findCreatedSinceByUser(UserInterface $user, \DateTime $date)
     */
    public function findCreatedSinceByUser(UserInterface $user, \DateTime $date): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.user = :user')
            ->andWhere('g.createdAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('g.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
