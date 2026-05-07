<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_time', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'max_participants')]
    private ?int $maxParticipants = null;

    #[ORM\Column(name: 'current_participants', options: ['default' => 0])]
    private int $currentParticipants = 0;

    #[ORM\Column(name: 'event_type', length: 50, nullable: true)]
    private ?string $eventType = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        options: ['default' => '0.00']
    )]
    private ?string $price = '0.00';

    #[ORM\Column(name: 'image_url', length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 50, options: ['default' => 'UPCOMING'])]
    private string $status = 'UPCOMING';

    #[ORM\Column(name: 'created_by', nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, EventRegistration>
     */
    #[ORM\OneToMany(
        targetEntity: EventRegistration::class,
        mappedBy: 'event',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $registrations;

    // =================== CONSTANTS ===================

    public const TYPES = [
        'Workshop' => 'WORKSHOP',
        'Group Therapy' => 'GROUP_THERAPY',
        'Seminar' => 'SEMINAR',
        'Social' => 'SOCIAL',
    ];

    public const STATUSES = [
        'Upcoming' => 'UPCOMING',
        'Ongoing' => 'ONGOING',
        'Completed' => 'COMPLETED',
        'Cancelled' => 'CANCELLED',
    ];

    // =================== CONSTRUCTOR ===================

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->currentParticipants = 0;
        $this->status = 'UPCOMING';
        $this->price = '0.00';
    }

    // =================== LIFECYCLE CALLBACKS ===================

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function initTimestamps(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
    }

    // =================== GETTERS & SETTERS ===================

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

    public function setDateTime(?\DateTimeInterface $dateTime): static
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

    public function getCurrentParticipants(): int
    {
        return $this->currentParticipants;
    }

    public function setCurrentParticipants(int $currentParticipants): static
    {
        $this->currentParticipants = max(0, $currentParticipants);
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

    public function getStatus(): string
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

    public function addRegistration(EventRegistration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setEvent($this);
        }
        return $this;
    }

    public function removeRegistration(EventRegistration $registration): static
    {
        if ($this->registrations->removeElement($registration)) {
            if ($registration->getEvent() === $this) {
                $registration->setEvent(null);
            }
        }
        return $this;
    }

    // =================== BUSINESS LOGIC ===================

    /**
     * Check if event has available spots
     */
    public function isAvailable(): bool
    {
        if ($this->maxParticipants === null) {
            return false;
        }
        return $this->currentParticipants < $this->maxParticipants
            && $this->status === 'UPCOMING';
    }

    /**
     * Get number of available spots
     */
    public function getAvailableSpots(): int
    {
        if ($this->maxParticipants === null) {
            return 0;
        }
        return max(0, $this->maxParticipants - $this->currentParticipants);
    }

    /**
     * Check if event is free
     */
    public function isFree(): bool
    {
        return floatval($this->price) == 0;
    }

    /**
     * Get price as float
     */
    public function getPriceAsFloat(): float
    {
        return floatval($this->price);
    }

    /**
     * Get occupancy percentage
     */
    public function getOccupancyPercentage(): float
    {
        if ($this->maxParticipants === null || $this->maxParticipants === 0) {
            return 0;
        }
        return min(100, round(($this->currentParticipants / $this->maxParticipants) * 100, 1));
    }

    /**
     * Check if event is sold out
     */
    public function isSoldOut(): bool
    {
        return $this->currentParticipants >= $this->maxParticipants;
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'UPCOMING';
    }

    /**
     * Check if event is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->status === 'ONGOING';
    }

    /**
     * Check if event is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    /**
     * Check if event is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }

    /**
     * Check if event is in the past
     */
    public function isPast(): bool
    {
        return $this->dateTime !== null && $this->dateTime < new \DateTime();
    }

    /**
     * Check if event is in the future
     */
    public function isFuture(): bool
    {
        return $this->dateTime !== null && $this->dateTime > new \DateTime();
    }

    /**
     * Get days until event
     */
    public function getDaysUntilEvent(): int
    {
        if ($this->dateTime === null) {
            return 0;
        }
        $now = new \DateTime();
        $diff = $now->diff($this->dateTime);
        return (int)$diff->days;
    }

    // =================== DISPLAY HELPERS ===================

    /**
     * Get Bootstrap badge class for status
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'UPCOMING' => 'bg-success',
            'ONGOING' => 'bg-primary',
            'COMPLETED' => 'bg-secondary',
            'CANCELLED' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get Bootstrap badge class for event type
     */
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

    /**
     * Get Font Awesome icon for event type
     */
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

    /**
     * Get status emoji
     */
    public function getStatusEmoji(): string
    {
        return match ($this->status) {
            'UPCOMING' => '🟢',
            'ONGOING' => '🔵',
            'COMPLETED' => '⚫',
            'CANCELLED' => '🔴',
            default => '⚪',
        };
    }

    /**
     * Get event type emoji
     */
    public function getEventTypeEmoji(): string
    {
        return match ($this->eventType) {
            'WORKSHOP' => '🛠',
            'GROUP_THERAPY' => '👥',
            'SEMINAR' => '🎓',
            'SOCIAL' => '🎉',
            default => '📅',
        };
    }

    /**
     * Get color for FullCalendar
     */
    public function getCalendarColor(): string
    {
        return match ($this->eventType) {
            'WORKSHOP' => '#50C878',
            'GROUP_THERAPY' => '#3A9B5E',
            'SEMINAR' => '#2E7D32',
            'SOCIAL' => '#9BC7B5',
            default => '#50C878',
        };
    }

    /**
     * Get formatted price string
     */
    public function getFormattedPrice(): string
    {
        if ($this->isFree()) {
            return 'FREE';
        }
        return '$' . number_format(floatval($this->price), 2);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDate(): string
    {
        if ($this->dateTime === null) {
            return 'N/A';
        }
        return $this->dateTime->format('M d, Y \a\t H:i');
    }

    /**
     * Get short title (for calendar/list)
     */
    public function getShortTitle(int $maxLength = 50): string
    {
        if ($this->title === null) {
            return '';
        }
        if (strlen($this->title) <= $maxLength) {
            return $this->title;
        }
        return substr($this->title, 0, $maxLength) . '...';
    }

    /**
     * Get short description
     */
    public function getShortDescription(int $maxLength = 100): string
    {
        if ($this->description === null) {
            return '';
        }
        if (strlen($this->description) <= $maxLength) {
            return $this->description;
        }
        return substr($this->description, 0, $maxLength) . '...';
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->title ?? 'Event #' . $this->id;
    }
}