<?php

namespace App\Repository;

use App\Entity\ContentPath;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContentPath>
 */
class ContentPathRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentPath::class);
    }

    public function findByContentNode($contentNode)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.contentNode = :node')
            ->setParameter('node', $contentNode)
            ->orderBy('p.accessedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserContent($user, $contentNode)
    {
        return $this->findOneBy([
            'user' => $user,
            'contentNode' => $contentNode,
        ]);
    }
}
