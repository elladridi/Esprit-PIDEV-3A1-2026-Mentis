<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findByFilters(
        string $type,
        string $search = '',
        string $sort = 'id',
        string $order = 'DESC',
        string $gender = '',
        string $ageGroup = '',
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('u');

        $qb->andWhere('u.type = :type')
           ->setParameter('type', $type);

        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.firstname LIKE :search OR u.lastname LIKE :search OR u.phone LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($gender) {
            $qb->andWhere('u.gender = :gender')
               ->setParameter('gender', $gender);
        }

        if ($ageGroup) {
            $this->addAgeGroupFilter($qb, $ageGroup);
        }

        $allowedSorts = ['id', 'firstname', 'lastname', 'email', 'phone', 'createdAt'];

        if (in_array($sort, $allowedSorts, true)) {
            $qb->orderBy('u.' . $sort, $order);
        }

        // ✅ FIX: LIMIT added
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    private function addAgeGroupFilter(QueryBuilder $qb, string $ageGroup): void
    {
        switch ($ageGroup) {
            case '0-18':
                $qb->andWhere('YEAR(u.dateofbirth) >= :year')
                   ->setParameter('year', date('Y') - 18);
                break;

            case '19-30':
                $qb->andWhere('YEAR(u.dateofbirth) BETWEEN :yearMin AND :yearMax')
                   ->setParameter('yearMin', date('Y') - 30)
                   ->setParameter('yearMax', date('Y') - 19);
                break;

            case '31-45':
                $qb->andWhere('YEAR(u.dateofbirth) BETWEEN :yearMin AND :yearMax')
                   ->setParameter('yearMin', date('Y') - 45)
                   ->setParameter('yearMax', date('Y') - 31);
                break;

            case '46-60':
                $qb->andWhere('YEAR(u.dateofbirth) BETWEEN :yearMin AND :yearMax')
                   ->setParameter('yearMin', date('Y') - 60)
                   ->setParameter('yearMax', date('Y') - 46);
                break;

            case '60+':
                $qb->andWhere('YEAR(u.dateofbirth) <= :year')
                   ->setParameter('year', date('Y') - 61);
                break;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatsByType(string $type): array
    {
        // ⚠️ Keep as is for report (you need all users here)
        $users = $this->findBy(['type' => $type]);

        $total = count($users);

        return [
            'total' => $total,
        ];
    }

    /**
     * @return User[]
     */
    public function findUsersWithFaceEnabled(int $limit = 20): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.faceEnabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($limit) // ✅ FIX
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findRecentlyRegisteredFaceUsers(int $days = 30, int $limit = 20): array
    {
        $date = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('u')
            ->andWhere('u.faceEnabled = :enabled')
            ->andWhere('u.faceRegisteredAt >= :date')
            ->setParameter('enabled', true)
            ->setParameter('date', $date)
            ->orderBy('u.faceRegisteredAt', 'DESC')
            ->setMaxResults($limit) // ✅ FIX
            ->getQuery()
            ->getResult();
    }
}