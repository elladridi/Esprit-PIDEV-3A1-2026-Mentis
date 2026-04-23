<?php

namespace App\Entity;

use App\Repository\ContentNodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
=======

>>>>>>> my-work-backup
#[ORM\Entity(repositoryClass: ContentNodeRepository::class)]
#[ORM\Table(name: 'content_node')]
class ContentNode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'node_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'pdf_path', length: 500, nullable: true)]
    private ?string $pdfPath = null;

<<<<<<< HEAD
    #[Vich\UploadableField(mapping: 'content_pdf', fileNameProperty: 'pdfPath')]
    private ?File $pdfFile = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

=======
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

>>>>>>> my-work-backup
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_node_id', referencedColumnName: 'node_id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parentNode = null;

    #[ORM\OneToMany(mappedBy: 'parentNode', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(name: 'assigned_users', type: 'text')]
    private string $assignedUsers = '[]';

    #[ORM\OneToMany(mappedBy: 'contentNode', targetEntity: ContentPath::class, orphanRemoval: true)]
    private Collection $contentPaths;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->children = new ArrayCollection();
        $this->contentPaths = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): self
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }

<<<<<<< HEAD
    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?File $pdfFile = null): self
    {
        $this->pdfFile = $pdfFile;

        if ($pdfFile !== null) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

=======
>>>>>>> my-work-backup
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

<<<<<<< HEAD
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

=======
>>>>>>> my-work-backup
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getParentNode(): ?self
    {
        return $this->parentNode;
    }

    public function setParentNode(?self $parentNode): self
    {
        $this->parentNode = $parentNode;

        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParentNode($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParentNode() === $this) {
                $child->setParentNode(null);
            }
        }

        return $this;
    }

    public function getAssignedUsers(): array
    {
        $decoded = json_decode($this->assignedUsers, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setAssignedUsers(array $assignedUsers): self
    {
        $this->assignedUsers = json_encode($assignedUsers);

        return $this;
    }

    /** @return Collection<int, ContentPath> */
    public function getContentPaths(): Collection
    {
        return $this->contentPaths;
    }

    public function addContentPath(ContentPath $contentPath): self
    {
        if (!$this->contentPaths->contains($contentPath)) {
            $this->contentPaths->add($contentPath);
            $contentPath->setContentNode($this);
        }

        return $this;
    }

    public function removeContentPath(ContentPath $contentPath): self
    {
        if ($this->contentPaths->removeElement($contentPath)) {
            if ($contentPath->getContentNode() === $this) {
                $contentPath->setContentNode(null);
            }
        }

        return $this;
    }
<<<<<<< HEAD

    public function getPdfPublicPath(): ?string
    {
        if ($this->pdfPath === null || $this->pdfPath === '') {
            return null;
        }

        if (str_starts_with($this->pdfPath, '/uploads/')) {
            return ltrim($this->pdfPath, '/');
        }

        if (str_starts_with($this->pdfPath, 'uploads/')) {
            return $this->pdfPath;
        }

        return 'uploads/' . ltrim($this->pdfPath, '/');
    }
=======
>>>>>>> my-work-backup
}
