<?php

namespace App\Repository;

use App\Entity\ContentNode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContentNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentNode::class);
    }

    // Méthode pour récupérer les contenus assignés à un utilisateur (filtrage PHP)
    public function findAssignedToUserPhp(int $userId): array
    {
        $allContent = $this->findAll();
        $assigned = [];
        
        foreach ($allContent as $content) {
            $assignedUsers = $content->getAssignedUsers();
            
            // Si assignedUsers est un tableau
            if (is_array($assignedUsers) && in_array($userId, $assignedUsers)) {
                $assigned[] = $content;
            }
            // Si assignedUsers est un string JSON
            elseif (is_string($assignedUsers)) {
                $users = json_decode($assignedUsers, true);
                if (is_array($users) && in_array($userId, $users)) {
                    $assigned[] = $content;
                }
            }
        }
        
        return $assigned;
    }

    // Pour Admin
    public function findForAdmin(string $search = '', string $sort = 'desc'): array
    {
        $qb = $this->createQueryBuilder('c');
        
        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $qb->orderBy('c.createdAt', $sort === 'asc' ? 'ASC' : 'DESC');
        
        return $qb->getQuery()->getResult();
    }

    // Pour Psychologue - AJOUTEZ CETTE MÉTHODE
    public function findForPsychologist($psychologist, string $search = '', string $sort = 'desc'): array
    {
        $qb = $this->createQueryBuilder('c');
        
        $qb->where('c.createdBy = :psychologist')
           ->setParameter('psychologist', $psychologist);
        
        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        $qb->orderBy('c.createdAt', $sort === 'asc' ? 'ASC' : 'DESC');
        
        return $qb->getQuery()->getResult();
    }
}