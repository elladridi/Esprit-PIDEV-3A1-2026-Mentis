<?php

namespace App\Repository;

use App\Entity\WellnessNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WellnessNotification>
 *
 * @method WellnessNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method WellnessNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method WellnessNotification[]    findAll()
 * @method WellnessNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WellnessNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WellnessNotification::class);
    }

    /**
     * @return WellnessNotification[] Returns an array of WellnessNotification objects
     */
    public function findByUserUnread(int $userId): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :userId')
            ->andWhere('w.isRead = :isRead')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
