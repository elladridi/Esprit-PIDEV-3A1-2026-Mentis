<?php

namespace App\Entity;

// On pointe vers le Repository du Bundle Analytics
use App\AnalyticsBundle\Repository\GoalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\GoalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Goal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a title for your goal.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Please provide a description for your goal.')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'Please choose a deadline for your goal.')]
    private ?\DateTimeImmutable $deadline = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $isCompleted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiAdvice = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $qrCode = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->isCompleted = false;
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getDeadline(): ?\DateTimeImmutable { return $this->deadline; }
    public function setDeadline(?\DateTimeImmutable $deadline): static { $this->deadline = $deadline; return $this; }
    public function isCompleted(): ?bool { return $this->isCompleted; }
    public function setIsCompleted(bool $isCompleted): static { $this->isCompleted = $isCompleted; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getAiAdvice(): ?string { return $this->aiAdvice; }
    public function setAiAdvice(?string $aiAdvice): static { $this->aiAdvice = $aiAdvice; return $this; }
    public function getQrCode(): ?string { return $this->qrCode; }
    public function setQrCode(?string $qrCode): static { $this->qrCode = $qrCode; return $this; }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void { $this->updatedAt = new \DateTimeImmutable(); }
}