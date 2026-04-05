<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Find questions by assessment ID
     * @return Question[]
     */
    public function findByAssessment(int $assessmentId): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.assessment = :assessmentId')
            ->setParameter('assessmentId', $assessmentId)
            ->orderBy('q.questionId', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count questions by assessment ID
     */
    public function countByAssessment(int $assessmentId): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.questionId)')
            ->andWhere('q.assessment = :assessmentId')
            ->setParameter('assessmentId', $assessmentId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}