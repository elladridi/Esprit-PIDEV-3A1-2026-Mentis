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
     * Find users by filters using Query Builder
     */
    public function findByFilters(
        string $type, 
        string $search = '', 
        string $sort = 'id', 
        string $order = 'DESC',
        string $gender = '',
        string $ageGroup = ''
    ): array {
        $qb = $this->createQueryBuilder('u');
        
        // Filter by type
        $qb->andWhere('u.type = :type')
           ->setParameter('type', $type);
        
        // Search filter (email, firstname, lastname, phone)
        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.firstname LIKE :search OR u.lastname LIKE :search OR u.phone LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Gender filter
        if ($gender) {
            $qb->andWhere('u.gender = :gender')
               ->setParameter('gender', $gender);
        }
        
        // Age group filter (using DQL with SUBSTRING for date)
        if ($ageGroup) {
            $this->addAgeGroupFilter($qb, $ageGroup);
        }
        
        // Sorting
        $allowedSorts = ['id', 'firstname', 'lastname', 'email', 'phone', 'createdAt'];
        if (in_array($sort, $allowedSorts)) {
            $qb->orderBy('u.' . $sort, $order);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Add age group filter to Query Builder
     */
    private function addAgeGroupFilter(QueryBuilder $qb, string $ageGroup): void
    {
        // Using SUBSTRING to extract year from dateofbirth (format YYYY-MM-DD)
        switch ($ageGroup) {
            case '0-18':
                $qb->andWhere('CAST(SUBSTRING(u.dateofbirth, 1, 4) AS integer) >= :year')
                   ->setParameter('year', date('Y') - 18);
                break;
            case '19-30':
                $qb->andWhere('CAST(SUBSTRING(u.dateofbirth, 1, 4) AS integer) BETWEEN :yearMin AND :yearMax')
                   ->setParameter('yearMin', date('Y') - 30)
                   ->setParameter('yearMax', date('Y') - 19);
                break;
            case '31-45':
                $qb->andWhere('CAST(SUBSTRING(u.dateofbirth, 1, 4) AS integer) BETWEEN :yearMin AND :yearMax')
                   ->setParameter('yearMin', date('Y') - 45)
                   ->setParameter('yearMax', date('Y') - 31);
                break;
            case '46-60':
                $qb->andWhere('CAST(SUBSTRING(u.dateofbirth, 1, 4) AS integer) BETWEEN :yearMin AND :yearMax')
                   ->setParameter('yearMin', date('Y') - 60)
                   ->setParameter('yearMax', date('Y') - 46);
                break;
            case '60+':
                $qb->andWhere('CAST(SUBSTRING(u.dateofbirth, 1, 4) AS integer) <= :year')
                   ->setParameter('year', date('Y') - 61);
                break;
        }
    }

    /**
     * Get statistics for a specific user type using DQL
     */
    public function getStatsByType(string $type): array
    {
        // Get all users of this type
        $users = $this->findBy(['type' => $type]);
        
        $total = count($users);
        $ages = [];
        $maleCount = 0;
        $femaleCount = 0;
        $otherCount = 0;
        $ageGroups = [
            '0-18' => 0,
            '19-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '60+' => 0
        ];
        
        foreach ($users as $user) {
            // Calculate age
            $age = $user->getAge();
            if ($age !== null) {
                $ages[] = $age;
                if ($age <= 18) $ageGroups['0-18']++;
                elseif ($age <= 30) $ageGroups['19-30']++;
                elseif ($age <= 45) $ageGroups['31-45']++;
                elseif ($age <= 60) $ageGroups['46-60']++;
                else $ageGroups['60+']++;
            }
            
            // Count gender
            $gender = $user->getGender();
            if ($gender === 'male') $maleCount++;
            elseif ($gender === 'female') $femaleCount++;
            elseif ($gender === 'other') $otherCount++;
        }
        
        $averageAge = !empty($ages) ? round(array_sum($ages) / count($ages), 1) : 0;
        $minAge = !empty($ages) ? min($ages) : 0;
        $maxAge = !empty($ages) ? max($ages) : 0;
        
        return [
            'total' => $total,
            'averageAge' => $averageAge,
            'minAge' => $minAge,
            'maxAge' => $maxAge,
            'gender' => [
                'male' => $maleCount,
                'female' => $femaleCount,
                'other' => $otherCount,
            ],
            'ageGroups' => $ageGroups,
        ];
    }
    
    /**
     * Get simple statistics for display
     */
    public function getSimpleStats(string $type): array
    {
        $users = $this->findBy(['type' => $type]);
        
        $total = count($users);
        $maleCount = 0;
        $femaleCount = 0;
        $totalAge = 0;
        $ageCount = 0;
        
        foreach ($users as $user) {
            $gender = $user->getGender();
            if ($gender === 'male') $maleCount++;
            elseif ($gender === 'female') $femaleCount++;
            
            $age = $user->getAge();
            if ($age !== null) {
                $totalAge += $age;
                $ageCount++;
            }
        }
        
        $averageAge = $ageCount > 0 ? round($totalAge / $ageCount, 1) : 0;
        
        return [
            'total' => $total,
            'averageAge' => $averageAge,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
        ];
    }
<<<<<<< HEAD

    // ==================== FACE RECOGNITION METHODS ====================
    
    /**
     * Find users with face recognition enabled
     * 
     * @return User[] Returns an array of User objects
     */
    public function findUsersWithFaceEnabled(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.faceEnabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a user by their face sample path
     * 
     * @param string $facePath The face image path
     * @return User|null
     */
    public function findUserByFaceSample(string $facePath): ?User
    {
        // This searches for users who have this face path in their faceData JSON
        $qb = $this->createQueryBuilder('u');
        
        // MySQL JSON search syntax
        if ($this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
            $qb->andWhere('JSON_SEARCH(u.faceData, "one", :path) IS NOT NULL')
               ->setParameter('path', $facePath);
        } else {
            // PostgreSQL or other databases - use LIKE (less efficient)
            $qb->andWhere('u.faceData LIKE :path')
               ->setParameter('path', '%' . $facePath . '%');
        }
        
        return $qb->setMaxResults(1)
                  ->getQuery()
                  ->getOneOrNullResult();
    }

    /**
     * Get users who registered for face ID in the last X days
     * 
     * @param int $days
     * @return User[]
     */
    public function findRecentlyRegisteredFaceUsers(int $days = 30): array
    {
        $date = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('u')
            ->andWhere('u.faceEnabled = :enabled')
            ->andWhere('u.faceRegisteredAt >= :date')
            ->setParameter('enabled', true)
            ->setParameter('date', $date)
            ->orderBy('u.faceRegisteredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics about face recognition usage
     * 
     * @return array
     */
    public function getFaceRecognitionStats(): array
    {
        $totalUsers = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $faceEnabledUsers = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.faceEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
        
        $avgSamplesPerUser = $this->createQueryBuilder('u')
            ->select('AVG(JSON_LENGTH(u.faceData))')
            ->where('u.faceData IS NOT NULL')
            ->andWhere('u.faceEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'total_users' => (int) $totalUsers,
            'face_enabled_users' => (int) $faceEnabledUsers,
            'adoption_rate' => $totalUsers > 0 ? round(($faceEnabledUsers / $totalUsers) * 100, 2) : 0,
            'average_samples_per_user' => round((float) $avgSamplesPerUser, 2),
        ];
    }

    // ==================== END FACE RECOGNITION METHODS ====================

    /**
     * Override the find method to add proper type hint
     * 
     * @param int $id
     * @param int|null $lockMode
     * @param int|null $lockVersion
     * @return User|null
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?User
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * Find by email (useful for login)
     * 
     * @param string $email
     * @return User|null
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
=======
>>>>>>> my-work-backup
}