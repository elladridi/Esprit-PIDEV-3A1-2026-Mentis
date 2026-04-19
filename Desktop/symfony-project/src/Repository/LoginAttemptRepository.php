<?php
// src/Repository/LoginAttemptRepository.php

namespace App\Repository;

use App\Entity\LoginAttempt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginAttempt>
 */
class LoginAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    public function countRecentFailedAttempts(string $email, string $ipAddress, int $minutes = 15): int
    {
        $cutoff = new \DateTime("-{$minutes} minutes");
        
        return $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.email = :email OR la.ipAddress = :ip')
            ->andWhere('la.attemptedAt >= :cutoff')
            ->andWhere('la.wasSuccessful = false')
            ->setParameter('email', $email)
            ->setParameter('ip', $ipAddress)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getSuspiciousAccounts(int $minFailedAttempts = 5, int $hours = 24): array
    {
        $cutoff = new \DateTime("-{$hours} hours");
        
        return $this->createQueryBuilder('la')
            ->select('la.email, COUNT(la.id) as failed_count, MAX(la.attemptedAt) as last_attempt')
            ->where('la.attemptedAt >= :cutoff')
            ->andWhere('la.wasSuccessful = false')
            ->setParameter('cutoff', $cutoff)
            ->groupBy('la.email')
            ->having('COUNT(la.id) >= :minAttempts')
            ->setParameter('minAttempts', $minFailedAttempts)
            ->orderBy('failed_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getRecentAttempts(int $limit = 50): array
    {
        return $this->createQueryBuilder('la')
            ->orderBy('la.attemptedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getAttemptsByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('la')
            ->where('la.email = :email')
            ->setParameter('email', $user->getEmail())
            ->orderBy('la.attemptedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $now = new \DateTime();
        $last24h = new \DateTime('-24 hours');
        $lastWeek = new \DateTime('-7 days');
        
        return [
            'total_attempts' => $this->createQueryBuilder('la')->select('COUNT(la.id)')->getQuery()->getSingleScalarResult(),
            'failed_attempts' => $this->createQueryBuilder('la')
                ->select('COUNT(la.id)')
                ->where('la.wasSuccessful = false')
                ->getQuery()->getSingleScalarResult(),
            'successful_attempts' => $this->createQueryBuilder('la')
                ->select('COUNT(la.id)')
                ->where('la.wasSuccessful = true')
                ->getQuery()->getSingleScalarResult(),
            'failed_last_24h' => $this->createQueryBuilder('la')
                ->select('COUNT(la.id)')
                ->where('la.wasSuccessful = false')
                ->andWhere('la.attemptedAt >= :cutoff')
                ->setParameter('cutoff', $last24h)
                ->getQuery()->getSingleScalarResult(),
            'unique_ips' => $this->createQueryBuilder('la')
                ->select('COUNT(DISTINCT la.ipAddress)')
                ->getQuery()->getSingleScalarResult(),
            'unique_emails' => $this->createQueryBuilder('la')
                ->select('COUNT(DISTINCT la.email)')
                ->getQuery()->getSingleScalarResult(),
        ];
    }

    public function clearOldAttempts(int $days = 30): int
    {
        $cutoff = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('la')
            ->delete()
            ->where('la.attemptedAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}