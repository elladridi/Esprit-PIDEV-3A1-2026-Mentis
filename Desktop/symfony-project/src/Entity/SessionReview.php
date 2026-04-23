<?php

namespace App\Entity;

use App\Repository\SessionReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SessionReviewRepository::class)]
#[ORM\Table(name: 'session_review')]
class SessionReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'review_id', type: 'integer')]
    private ?int $reviewId = null;

    #[ORM\Column(name: 'session_id', type: 'integer')]
    private ?int $sessionId = null;

    #[ORM\Column(name: 'patient_id', type: 'integer')]
    private ?int $patientId = null;

    #[ORM\Column(name: 'rating', type: 'integer')]
    private ?int $rating = null;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'review_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reviewDate = null;

    #[ORM\Column(name: 'is_appropriate', type: 'boolean', nullable: true, options: ['default' => true])]
    private ?bool $isAppropriate = true;

    // Not mapped in database - joined from Session entity
    private ?string $sessionTitle = null;
    private ?\DateTimeInterface $sessionDate = null;
    private ?\DateTimeInterface $startTime = null;
    private ?\DateTimeInterface $endTime = null;
    private ?string $location = null;
    private ?string $sessionType = null;

    // Not mapped - joined from User entity
    private ?string $patientName = null;

    public function __construct()
    {
        $this->reviewDate = new \DateTime();
    }

    // ========== GETTERS ==========

    public function getReviewId(): ?int { return $this->reviewId; }
    public function getSessionId(): ?int { return $this->sessionId; }
    public function getPatientId(): ?int { return $this->patientId; }
    public function getRating(): ?int { return $this->rating; }
    public function getComment(): ?string { return $this->comment; }
    public function getReviewDate(): ?\DateTimeInterface { return $this->reviewDate; }
    public function getIsAppropriate(): ?bool { return $this->isAppropriate; }
    public function getSessionTitle(): ?string { return $this->sessionTitle; }
    public function getSessionDate(): ?\DateTimeInterface { return $this->sessionDate; }
    public function getStartTime(): ?\DateTimeInterface { return $this->startTime; }
    public function getEndTime(): ?\DateTimeInterface { return $this->endTime; }
    public function getLocation(): ?string { return $this->location; }
    public function getSessionType(): ?string { return $this->sessionType; }
    public function getPatientName(): ?string { return $this->patientName; }

    // ========== SETTERS ==========

    public function setReviewId(int $reviewId): self { $this->reviewId = $reviewId; return $this; }
    public function setSessionId(int $sessionId): self { $this->sessionId = $sessionId; return $this; }
    public function setPatientId(int $patientId): self { $this->patientId = $patientId; return $this; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }
    public function setComment(?string $comment): self { $this->comment = $comment; return $this; }
    public function setReviewDate(?\DateTimeInterface $reviewDate): self { $this->reviewDate = $reviewDate; return $this; }
    public function setIsAppropriate(?bool $isAppropriate): self { $this->isAppropriate = $isAppropriate; return $this; }
    public function setSessionTitle(?string $sessionTitle): self { $this->sessionTitle = $sessionTitle; return $this; }
    public function setSessionDate(?\DateTimeInterface $sessionDate): self { $this->sessionDate = $sessionDate; return $this; }
    public function setStartTime(?\DateTimeInterface $startTime): self { $this->startTime = $startTime; return $this; }
    public function setEndTime(?\DateTimeInterface $endTime): self { $this->endTime = $endTime; return $this; }
    public function setLocation(?string $location): self { $this->location = $location; return $this; }
    public function setSessionType(?string $sessionType): self { $this->sessionType = $sessionType; return $this; }
    public function setPatientName(?string $patientName): self { $this->patientName = $patientName; return $this; }

    // Helper method
    public function getStarRating(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}