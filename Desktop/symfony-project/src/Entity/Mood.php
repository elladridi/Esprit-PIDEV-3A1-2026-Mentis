<?php

namespace App\Entity;

// On pointe vers le Repository du Bundle Analytics

use App\AnalyticsBundle\Repository\MoodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\MoodRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Mood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please select your current feeling.')]
    private ?string $feeling = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Please provide a note about your mood.')]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recommendedTrackName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recommendedArtistName = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $recommendedTrackUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $recommendedPreviewUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $recommendedImageUrl = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getFeeling(): ?string { return $this->feeling; }
    public function setFeeling(string $feeling): static { $this->feeling = $feeling; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getRecommendedTrackName(): ?string { return $this->recommendedTrackName; }
    public function setRecommendedTrackName(?string $recommendedTrackName): static { $this->recommendedTrackName = $recommendedTrackName; return $this; }
    public function getRecommendedArtistName(): ?string { return $this->recommendedArtistName; }
    public function setRecommendedArtistName(?string $recommendedArtistName): static { $this->recommendedArtistName = $recommendedArtistName; return $this; }
    public function getRecommendedTrackUrl(): ?string { return $this->recommendedTrackUrl; }
    public function setRecommendedTrackUrl(?string $recommendedTrackUrl): static { $this->recommendedTrackUrl = $recommendedTrackUrl; return $this; }
    public function getRecommendedPreviewUrl(): ?string { return $this->recommendedPreviewUrl; }
    public function setRecommendedPreviewUrl(?string $recommendedPreviewUrl): static { $this->recommendedPreviewUrl = $recommendedPreviewUrl; return $this; }
    public function getRecommendedImageUrl(): ?string { return $this->recommendedImageUrl; }
    public function setRecommendedImageUrl(?string $recommendedImageUrl): static { $this->recommendedImageUrl = $recommendedImageUrl; return $this; }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void { $this->updatedAt = new \DateTimeImmutable(); }
}