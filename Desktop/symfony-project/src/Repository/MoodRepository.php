<?php

namespace App\Repository;

use App\Entity\Mood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mood>
 */
class MoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mood::class);
    }

    public function findByUserOrdered($user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBetweenDates($user, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.createdAt >= :startDate')
            ->andWhere('m.createdAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFilteredForUser($user, ?string $query, ?string $feeling, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user);

        if ($query) {
            $qb->andWhere('m.note LIKE :query OR m.feeling LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($feeling) {
            $qb->andWhere('m.feeling = :feeling')
               ->setParameter('feeling', $feeling);
        }

        switch ($sort) {
            case 'oldest': $qb->orderBy('m.createdAt', 'ASC'); break;
            case 'feeling': $qb->orderBy('m.feeling', 'ASC'); break;
            default: $qb->orderBy('m.createdAt', 'DESC'); break;
        }

        return $qb->getQuery()->getResult();
    }

    // Cette méthode est celle appelée par ton Controller à la ligne 35
    // src/Repository/MoodRepository.php

public function getFeelingCountsForUser($user): array
{
    return $this->createQueryBuilder('m')
        ->select('m.feeling, COUNT(m.id) as count')
        ->where('m.user = :user')
        ->setParameter('user', $user)
        ->groupBy('m.feeling')
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