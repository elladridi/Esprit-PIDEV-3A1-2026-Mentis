<?php

namespace App\Entity;

use App\Repository\ContentPathRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContentPathRepository::class)]
#[ORM\Table(name: 'content_path')]
class ContentPath
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'path_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ContentNode::class, inversedBy: 'contentPaths')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'node_id', nullable: false, onDelete: 'CASCADE')]
    private ?ContentNode $contentNode = null;

    #[ORM\Column(name: 'accessed_at', type: 'datetime')]
    private \DateTimeInterface $accessedAt;

    public function __construct()
    {
        $this->accessedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getContentNode(): ?ContentNode
    {
        return $this->contentNode;
    }

    public function setContentNode(ContentNode $contentNode): self
    {
        $this->contentNode = $contentNode;

        return $this;
    }

    public function getAccessedAt(): \DateTimeInterface
    {
        return $this->accessedAt;
    }

    public function setAccessedAt(\DateTimeInterface $accessedAt): self
    {
        $this->accessedAt = $accessedAt;

        return $this;
    }
}
