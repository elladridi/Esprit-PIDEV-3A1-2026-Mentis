<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\Table(name: 'sessions')]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'session_id', type: 'integer')]
    private ?int $sessionId = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'session_date', type: 'date')]
    private ?\DateTimeInterface $sessionDate = null;

    #[ORM\Column(name: 'start_time', type: 'time')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'time')]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(name: 'location', type: 'string', length: 255)]
    private ?string $location = null;

    #[ORM\Column(name: 'session_type', type: 'string', length: 100)]
    private ?string $sessionType = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: true, options: ['default' => 'scheduled'])]
    private ?string $status = 'scheduled';

    #[ORM\Column(name: 'reserved_by', type: 'integer', nullable: true)]
    private ?int $reservedBy = null;

    #[ORM\Column(name: 'reserved_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reservedAt = null;

    #[ORM\Column(name: 'category', type: 'string', length: 100, nullable: true, options: ['default' => 'General'])]
    private ?string $category = 'General';

    #[ORM\Column(name: 'popularity', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $popularity = 0;

    #[ORM\Column(name: 'average_rating', type: 'float', nullable: true, options: ['default' => 0])]
    private ?float $averageRating = 0.0;

    #[ORM\Column(name: 'meeting_link', type: 'string', length: 500, nullable: true)]
    private ?string $meetingLink = null;

    #[ORM\Column(name: 'meeting_started', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $meetingStarted = false;

    // FIXED: Changed from 'meeting_end' to 'meeting_ended'
    #[ORM\Column(name: 'meeting_ended', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $meetingEnd = false;

    #[ORM\Column(name: 'reminder_sent', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $reminderSent = false;

    #[ORM\Column(name: 'patient_confirmed', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $patientConfirmed = false;

    #[ORM\Column(name: 'confirmed_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $confirmedAt = null;

    #[ORM\Column(name: 'max_participants', type: 'integer', nullable: true, options: ['default' => 20])]
    private ?int $maxParticipants = 20;

    #[ORM\Column(name: 'current_participants', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $currentParticipants = 0;

    #[ORM\Column(name: 'price', type: 'decimal', precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $price = '0.00';

    // ========== CONSTRUCTOR ==========
    
    public function __construct()
    {
        $this->status = 'scheduled';
        $this->category = 'General';
        $this->popularity = 0;
        $this->averageRating = 0.0;
        $this->meetingStarted = false;
        $this->meetingEnd = false;
        $this->reminderSent = false;
        $this->patientConfirmed = false;
        $this->maxParticipants = 20;
        $this->currentParticipants = 0;
        $this->price = '0.00';
    }

    // ========== GETTERS ==========

    public function getSessionId(): ?int { return $this->sessionId; }
    public function getTitle(): ?string { return $this->title; }
    public function getSessionDate(): ?\DateTimeInterface { return $this->sessionDate; }
    public function getStartTime(): ?\DateTimeInterface { return $this->startTime; }
    public function getEndTime(): ?\DateTimeInterface { return $this->endTime; }
    public function getLocation(): ?string { return $this->location; }
    public function getSessionType(): ?string { return $this->sessionType; }
    public function getStatus(): ?string { return $this->status; }
    public function getReservedBy(): ?int { return $this->reservedBy; }
    public function getReservedAt(): ?\DateTimeInterface { return $this->reservedAt; }
    public function getCategory(): ?string { return $this->category; }
    public function getPopularity(): ?int { return $this->popularity; }
    public function getAverageRating(): ?float { return $this->averageRating; }
    public function getMeetingLink(): ?string { return $this->meetingLink; }
    public function isMeetingStarted(): ?bool { return $this->meetingStarted; }
    public function isMeetingEnd(): ?bool { return $this->meetingEnd; }
    public function isReminderSent(): ?bool { return $this->reminderSent; }
    public function isPatientConfirmed(): ?bool { return $this->patientConfirmed; }
    public function getConfirmedAt(): ?\DateTimeInterface { return $this->confirmedAt; }
    public function getMaxParticipants(): ?int { return $this->maxParticipants; }
    public function getCurrentParticipants(): ?int { return $this->currentParticipants; }
    public function getPrice(): ?string { return $this->price; }

    // ========== SETTERS ==========

    public function setSessionId(int $sessionId): self { $this->sessionId = $sessionId; return $this; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function setSessionDate(\DateTimeInterface $sessionDate): self { $this->sessionDate = $sessionDate; return $this; }
    public function setStartTime(\DateTimeInterface $startTime): self { $this->startTime = $startTime; return $this; }
    public function setEndTime(\DateTimeInterface $endTime): self { $this->endTime = $endTime; return $this; }
    public function setLocation(string $location): self { $this->location = $location; return $this; }
    public function setSessionType(string $sessionType): self { $this->sessionType = $sessionType; return $this; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function setReservedBy(?int $reservedBy): self { $this->reservedBy = $reservedBy; return $this; }
    public function setReservedAt(?\DateTimeInterface $reservedAt): self { $this->reservedAt = $reservedAt; return $this; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }
    public function setPopularity(int $popularity): self { $this->popularity = $popularity; return $this; }
    public function setAverageRating(float $averageRating): self { $this->averageRating = $averageRating; return $this; }
    public function setMeetingLink(?string $meetingLink): self { $this->meetingLink = $meetingLink; return $this; }
    public function setMeetingStarted(bool $meetingStarted): self { $this->meetingStarted = $meetingStarted; return $this; }
    public function setMeetingEnd(bool $meetingEnd): self { $this->meetingEnd = $meetingEnd; return $this; }
    public function setReminderSent(bool $reminderSent): self { $this->reminderSent = $reminderSent; return $this; }
    public function setPatientConfirmed(bool $patientConfirmed): self { $this->patientConfirmed = $patientConfirmed; return $this; }
    public function setConfirmedAt(?\DateTimeInterface $confirmedAt): self { $this->confirmedAt = $confirmedAt; return $this; }
    public function setMaxParticipants(int $maxParticipants): self { $this->maxParticipants = $maxParticipants; return $this; }
    public function setCurrentParticipants(int $currentParticipants): self { $this->currentParticipants = $currentParticipants; return $this; }
    public function setPrice(string $price): self { $this->price = $price; return $this; }

    // ========== HELPER METHODS ==========

    public function isAvailable(): bool
    {
        return $this->reservedBy === null;
    }

    public function isReservedBy(int $patientId): bool
    {
        return $this->reservedBy !== null && $this->reservedBy === $patientId;
    }

    public function incrementPopularity(): self
    {
        $this->popularity++;
        return $this;
    }

    public function updateAverageRating(int $newRating): self
    {
        $this->averageRating = ($this->averageRating + $newRating) / 2;
        return $this;
    }

    public function inferCategory(): string
    {
        if ($this->category !== null && $this->category !== 'General') {
            return $this->category;
        }

        return match (strtolower($this->sessionType ?? '')) {
            'individual' => 'Personal Growth',
            'group' => 'Social Support',
            'family' => 'Family Therapy',
            'couple' => 'Relationship',
            'online' => 'Convenient Care',
            default => 'General',
        };
    }

    public function hasAvailableSpots(): bool
    {
        return $this->currentParticipants < $this->maxParticipants;
    }

    public function getRemainingSpots(): int
    {
        return $this->maxParticipants - $this->currentParticipants;
    }
}