<?php

namespace App\Repository;

use App\Entity\ContentNode;
use App\Entity\User;
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

    /**
     * Méthode pour récupérer les contenus assignés à un utilisateur.
     *
     * @return ContentNode[]
     */
    public function findAssignedToUserPhp(int $userId): array
    {
        $allContent = $this->findAll();
        $assigned = [];

        foreach ($allContent as $content) {
            $assignedUsers = $content->getAssignedUsers();

            if (is_array($assignedUsers) && in_array($userId, $assignedUsers, true)) {
                $assigned[] = $content;
            } elseif (is_string($assignedUsers)) {
                $users = json_decode($assignedUsers, true);

                if (is_array($users) && in_array($userId, $users, true)) {
                    $assigned[] = $content;
                }
            }
        }

        return $assigned;
    }

    /**
     * @return ContentNode[]
     */
    public function findAssignedToUser(int $userId): array
    {
        return $this->findAssignedToUserPhp($userId);
    }

    /**
     * Pour Admin.
     *
     * @return ContentNode[]
     */
    public function findForAdmin(string $search = '', string $sort = 'desc'): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($search !== '') {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('c.createdAt', $sort === 'asc' ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Pour Psychologue.
     *
     * @return ContentNode[]
     */
    public function findForPsychologist(User $psychologist, string $search = '', string $sort = 'desc'): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where('c.createdBy = :psychologist')
            ->setParameter('psychologist', $psychologist);

        if ($search !== '') {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('c.createdAt', $sort === 'asc' ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }
}