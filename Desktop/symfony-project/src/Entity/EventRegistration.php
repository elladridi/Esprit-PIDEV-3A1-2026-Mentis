<?php

namespace App\Entity;

use App\Repository\EventRegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRegistrationRepository::class)]
#[ORM\Table(name: 'event_registrations')]
#[ORM\UniqueConstraint(name: 'unique_email_event', columns: ['email', 'event_id'])]
#[ORM\HasLifecycleCallbacks]
class EventRegistration
{
    // =================== CONSTANTS ===================

    public const TICKET_TYPES = [
        'Standard' => 'STANDARD',
        'VIP (+50%)' => 'VIP',
        'Early Bird (-20%)' => 'EARLY_BIRD',
        'Group (-10%)' => 'GROUP',
    ];

    public const STATUSES = [
        'Confirmed' => 'CONFIRMED',
        'Pending' => 'PENDING',
        'Cancelled' => 'CANCELLED',
    ];

    public const PAYMENT_METHODS = [
        'Credit Card' => 'CREDIT_CARD',
        'PayPal' => 'PAYPAL',
        'Bank Transfer' => 'BANK_TRANSFER',
        'Cash' => 'CASH',
        'Free' => 'FREE',
    ];

    public const TICKET_MULTIPLIERS = [
        'STANDARD' => 1.0,
        'VIP' => 1.5,
        'EARLY_BIRD' => 0.8,
        'GROUP' => 0.9,
    ];

    // =================== FIELDS ===================

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'user_name', length: 255)]
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot exceed {{ limit }} characters'
    )]
    private ?string $userName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9+\-\s\(\)]+$/',
        message: 'Please enter a valid phone number'
    )]
    private ?string $phone = null;

    #[ORM\Column(name: 'ticket_type', length: 50, options: ['default' => 'STANDARD'])]
    #[Assert\Choice(
        choices: ['STANDARD', 'VIP', 'EARLY_BIRD', 'GROUP'],
        message: 'Invalid ticket type'
    )]
    private string $ticketType = 'STANDARD';

    #[ORM\Column(name: 'number_of_tickets')]
    #[Assert\NotBlank(message: 'Number of tickets is required')]
    #[Assert\Positive(message: 'Number of tickets must be at least 1')]
    #[Assert\LessThanOrEqual(
        value: 10,
        message: 'Maximum 10 tickets per registration'
    )]
    private int $numberOfTickets = 1;

    #[ORM\Column(
        name: 'total_price',
        type: Types::DECIMAL,
        precision: 10,
        scale: 2,
        options: ['default' => '0.00']
    )]
    private string $totalPrice = '0.00';

    #[ORM\Column(length: 50, options: ['default' => 'CONFIRMED'])]
    #[Assert\Choice(
        choices: ['CONFIRMED', 'PENDING', 'CANCELLED'],
        message: 'Invalid status'
    )]
    private string $status = 'CONFIRMED';

    #[ORM\Column(name: 'payment_method', length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(name: 'special_requests', type: Types::TEXT, nullable: true)]
    private ?string $specialRequests = null;

    #[ORM\Column(name: 'registration_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodePath = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $confirmationNumber = null;

    // =================== CONSTRUCTOR ===================

    public function __construct()
    {
        $this->registrationDate = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->ticketType = 'STANDARD';
        $this->numberOfTickets = 1;
        $this->totalPrice = '0.00';
        $this->status = 'CONFIRMED';
    }

    // =================== LIFECYCLE CALLBACKS ===================

    #[ORM\PrePersist]
    public function initTimestamps(): void
    {
        if ($this->registrationDate === null) {
            $this->registrationDate = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PostPersist]
    public function generateConfirmationNumber(): void
    {
        if ($this->confirmationNumber === null && $this->id !== null) {
            $this->confirmationNumber = 'REG-' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
        }
    }

    // =================== GETTERS & SETTERS ===================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        if ($user !== null) {
            $this->userName = trim($user->getFirstname() . ' ' . $user->getLastname());
            $this->email = $user->getEmail();
            $this->phone = $user->getPhone();
        }
        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): static
    {
        $this->userName = $userName ? trim($userName) : null;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email ? strtolower(trim($email)) : null;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getTicketType(): string
    {
        return $this->ticketType;
    }

    public function setTicketType(?string $ticketType): static
    {
        $this->ticketType = $ticketType ?? 'STANDARD';
        return $this;
    }

    public function getNumberOfTickets(): int
    {
        return $this->numberOfTickets;
    }

    public function setNumberOfTickets(?int $numberOfTickets): static
    {
        $this->numberOfTickets = max(1, $numberOfTickets ?? 1);
        return $this;
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): static
    {
        $this->totalPrice = $totalPrice ?? '0.00';
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status ?? 'CONFIRMED';
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod ?? 'CREDIT_CARD';
        return $this;
    }

    public function getSpecialRequests(): ?string
    {
        return $this->specialRequests;
    }

    public function setSpecialRequests(?string $specialRequests): static
    {
        $this->specialRequests = $specialRequests;
        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): static
    {
        $this->registrationDate = $registrationDate;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getQrCodePath(): ?string
    {
        return $this->qrCodePath;
    }

    public function setQrCodePath(?string $qrCodePath): static
    {
        $this->qrCodePath = $qrCodePath;
        return $this;
    }

    public function getConfirmationNumber(): ?string
    {
        return $this->confirmationNumber;
    }

    public function setConfirmationNumber(?string $confirmationNumber): static
    {
        $this->confirmationNumber = $confirmationNumber;
        return $this;
    }

    // =================== BUSINESS LOGIC ===================

    /**
     * Check if registration is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'CONFIRMED';
    }

    /**
     * Check if registration is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }

    /**
     * Check if registration is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if ticket is free
     */
    public function isFreeTicket(): bool
    {
        return floatval($this->totalPrice) == 0;
    }

    /**
     * Get total price as float
     */
    public function getTotalPriceAsFloat(): float
    {
        return floatval($this->totalPrice);
    }

    /**
     * Get price per ticket
     */
    public function getPricePerTicket(): float
    {
        if ($this->numberOfTickets === 0) {
            return 0.0;
        }
        return floatval($this->totalPrice) / $this->numberOfTickets;
    }

    /**
     * Get ticket type multiplier
     */
    public function getTicketMultiplier(): float
    {
        return self::TICKET_MULTIPLIERS[$this->ticketType] ?? 1.0;
    }

    /**
     * Calculate total price based on event price and ticket type
     */
    public function calculateTotalPrice(?Event $event = null): float
    {
        $baseEvent = $event ?? $this->event;
        if ($baseEvent === null) {
            return 0.0;
        }
        $basePrice = floatval($baseEvent->getPrice());
        $multiplier = $this->getTicketMultiplier();
        return $basePrice * $this->numberOfTickets * $multiplier;
    }

    /**
     * Get formatted confirmation number
     */
    public function getFormattedConfirmationNumber(): string
    {
        if ($this->confirmationNumber !== null) {
            return $this->confirmationNumber;
        }
        return 'REG-' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
    }

    // =================== DISPLAY HELPERS ===================

    /**
     * Get status emoji
     */
    public function getStatusEmoji(): string
    {
        return match ($this->status) {
            'CONFIRMED' => '✅',
            'PENDING' => '⏳',
            'CANCELLED' => '❌',
            default => '❓',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'CONFIRMED' => 'bg-success',
            'PENDING' => 'bg-warning',
            'CANCELLED' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get ticket type badge class
     */
    public function getTicketTypeBadgeClass(): string
    {
        return match ($this->ticketType) {
            'VIP' => 'bg-warning text-dark',
            'EARLY_BIRD' => 'bg-info',
            'GROUP' => 'bg-primary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get ticket type label
     */
    public function getTicketTypeLabel(): string
    {
        return match ($this->ticketType) {
            'VIP' => 'VIP (+50%)',
            'EARLY_BIRD' => 'Early Bird (-20%)',
            'GROUP' => 'Group (-10%)',
            default => 'Standard',
        };
    }

    /**
     * Get formatted total price
     */
    public function getFormattedTotalPrice(): string
    {
        if ($this->isFreeTicket()) {
            return 'FREE';
        }
        return '$' . number_format(floatval($this->totalPrice), 2);
    }

    /**
     * Get formatted registration date
     */
    public function getFormattedRegistrationDate(): string
    {
        if ($this->registrationDate === null) {
            return 'N/A';
        }
        return $this->registrationDate->format('M d, Y \a\t H:i');
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return sprintf(
            'Registration #%s - %s (%s)',
            $this->getFormattedConfirmationNumber(),
            $this->userName ?? 'Unknown',
            $this->event ? $this->event->getTitle() : 'No Event'
        );
    }
}