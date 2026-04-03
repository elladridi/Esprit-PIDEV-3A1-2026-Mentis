<?php

namespace App\Repository;

use App\Entity\ContentNode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContentNode>
 */
class ContentNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentNode::class);
    }

    public function findByCreatorOrAll($user, bool $isAdmin = false)
    {
        if ($isAdmin) {
            return $this->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAssignedToUser(int $userId)
    {
        $value = '"' . $userId . '"';

        return $this->createQueryBuilder('c')
            ->andWhere('c.assignedUsers LIKE :value')
            ->setParameter('value', '%'. $value . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findForAdmin(string $search = null, string $sortOrder = 'DESC')
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', strtoupper($sortOrder));

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findForPsychologist($user, string $search = null, string $sortOrder = 'DESC')
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', strtoupper($sortOrder));

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findAssignedToUserWithSearch(int $userId, string $search = null, string $sortOrder = 'DESC')
    {
        $value = '"' . $userId . '"';

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.assignedUsers LIKE :value')
            ->setParameter('value', '%'. $value . '%')
            ->orderBy('c.createdAt', strtoupper($sortOrder));

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }
}

