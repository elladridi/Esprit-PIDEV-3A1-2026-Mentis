<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Title must be at least 3 characters')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_time', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Date and time are required')]
    #[Assert\GreaterThan('today', message: 'Event date must be in the future')]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'max_participants')]
    #[Assert\NotBlank(message: 'Maximum participants is required')]
    #[Assert\Positive(message: 'Maximum participants must be a positive number')]
    #[Assert\LessThan(1001, message: 'Maximum participants cannot exceed 1000')]
    private ?int $maxParticipants = null;

    #[ORM\Column(name: 'current_participants', options: ['default' => 0])]
    private ?int $currentParticipants = 0;

    #[ORM\Column(name: 'event_type', length: 50, nullable: true)]
    #[Assert\Choice(choices: ['WORKSHOP', 'GROUP_THERAPY', 'SEMINAR', 'SOCIAL'], message: 'Invalid event type')]
    private ?string $eventType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Price cannot be negative')]
    private ?string $price = '0.00';

    #[ORM\Column(name: 'image_url', length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 50, options: ['default' => 'UPCOMING'])]
    #[Assert\Choice(choices: ['UPCOMING', 'ONGOING', 'COMPLETED', 'CANCELLED'], message: 'Invalid status')]
    private ?string $status = 'UPCOMING';

    #[ORM\Column(name: 'created_by', nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, EventRegistration>
     */
    #[ORM\OneToMany(targetEntity: EventRegistration::class, mappedBy: 'event', cascade: ['remove'])]
    private Collection $registrations;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): static
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function getCurrentParticipants(): ?int
    {
        return $this->currentParticipants;
    }

    public function setCurrentParticipants(int $currentParticipants): static
    {
        $this->currentParticipants = $currentParticipants;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, EventRegistration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    // Business Logic Methods

    public function isAvailable(): bool
    {
        return $this->currentParticipants < $this->maxParticipants;
    }

    public function getAvailableSpots(): int
    {
        return $this->maxParticipants - $this->currentParticipants;
    }

    public function isFree(): bool
    {
        return floatval($this->price) == 0;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'UPCOMING' => 'bg-primary',
            'ONGOING' => 'bg-success',
            'COMPLETED' => 'bg-secondary',
            'CANCELLED' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getEventTypeBadgeClass(): string
    {
        return match ($this->eventType) {
            'WORKSHOP' => 'bg-info',
            'GROUP_THERAPY' => 'bg-warning',
            'SEMINAR' => 'bg-success',
            'SOCIAL' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getEventTypeIcon(): string
    {
        return match ($this->eventType) {
            'WORKSHOP' => 'fa-chalkboard-user',
            'GROUP_THERAPY' => 'fa-users',
            'SEMINAR' => 'fa-person-chalkboard',
            'SOCIAL' => 'fa-calendar-heart',
            default => 'fa-calendar',
        };
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTime();
    }
}