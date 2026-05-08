<?php

namespace App\AnalyticsBundle\Repository;

use App\Entity\Mood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Mood>
 */
class MoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mood::class);
    }

    /**
     * getFeelingCountsForUser(UserInterface $user) : Retourne le décompte des émotions par type.
     */
    public function getFeelingCountsForUser(UserInterface $user): array
    {
        $results = $this->createQueryBuilder('m')
            ->select('m.feeling, COUNT(m.id) as count')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->groupBy('m.feeling')
            ->getQuery()
            ->getResult();

        $stats = ['very_happy' => 0, 'happy' => 0, 'neutral' => 0, 'sad' => 0, 'very_sad' => 0];
        foreach ($results as $result) {
            if (isset($stats[$result['feeling']])) {
                $stats[$result['feeling']] = (int) $result['count'];
            }
        }

        return $stats;
    }

    /**
     * findCreatedSinceByUser(UserInterface $user, \DateTime $date)
     */
    public function findCreatedSinceByUser(UserInterface $user, \DateTime $date): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.createdAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
