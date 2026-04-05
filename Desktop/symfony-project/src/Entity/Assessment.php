<?php

namespace App\Entity;

use App\Repository\AssessmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssessmentRepository::class)]
#[ORM\Table(name: 'assessment')]
class Assessment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'assessment_id', type: 'integer')]
    private ?int $assessmentId = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'type', type: 'string', length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'status', type: 'string', length: 50, nullable: true)]
    private ?string $status = 'Active';

    #[ORM\Column(name: 'image_path', type: 'string', length: 500, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'assessment', cascade: ['remove'])]
    private Collection $questions;

    #[ORM\OneToMany(targetEntity: AssessmentResult::class, mappedBy: 'assessment')]
    private Collection $results;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->results = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters
    public function getAssessmentId(): ?int { return $this->assessmentId; }
    public function setAssessmentId(int $assessmentId): self { $this->assessmentId = $assessmentId; return $this; }
    
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    
    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): self { $this->type = $type; return $this; }
    
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    
    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): self { $this->imagePath = $imagePath; return $this; }
    
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    
    public function getQuestions(): Collection { return $this->questions; }
    public function getResults(): Collection { return $this->results; }
}