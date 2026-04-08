<?php

namespace App\Entity;

use App\Repository\EventRegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRegistrationRepository::class)]
#[ORM\Table(name: 'event_registrations')]
#[ORM\UniqueConstraint(name: 'unique_email_event', columns: ['email', 'event_id'])]
class EventRegistration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'user_name', length: 255)]
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Name must be at least 2 characters')]
    private ?string $userName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Regex(pattern: '/^[0-9+\-\s]+$/', message: 'Please enter a valid phone number')]
    private ?string $phone = null;

    #[ORM\Column(name: 'ticket_type', length: 50, options: ['default' => 'STANDARD'])]
    #[Assert\Choice(choices: ['STANDARD', 'VIP'], message: 'Invalid ticket type')]
    private ?string $ticketType = 'STANDARD';

    #[ORM\Column(name: 'number_of_tickets', options: ['default' => 1])]
    #[Assert\NotBlank(message: 'Number of tickets is required')]
    #[Assert\Positive(message: 'Number of tickets must be at least 1')]
    #[Assert\LessThanOrEqual(value: 10, message: 'Maximum 10 tickets per registration')]
    private ?int $numberOfTickets = 1;

    #[ORM\Column(name: 'total_price', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $totalPrice = '0.00';

    #[ORM\Column(length: 50, options: ['default' => 'CONFIRMED'])]
    #[Assert\Choice(choices: ['CONFIRMED', 'PENDING', 'CANCELLED'], message: 'Invalid status')]
    private ?string $status = 'CONFIRMED';

    #[ORM\Column(name: 'payment_method', length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(name: 'special_requests', type: Types::TEXT, nullable: true)]
    private ?string $specialRequests = null;

    #[ORM\Column(name: 'registration_date', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodePath = null;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $confirmationNumber = null;

    public function __construct()
    {
        $this->registrationDate = new \DateTime();
        $this->confirmationNumber = 'REG-' . uniqid();
    }

    // Getters and Setters

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
        if ($user) {
            $this->userName = $user->getFirstname() . ' ' . $user->getLastname();
            $this->email = $user->getEmail();
            $this->phone = $user->getPhone();
        }
        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
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

    public function getTicketType(): ?string
    {
        return $this->ticketType;
    }

    public function setTicketType(string $ticketType): static
    {
        $this->ticketType = $ticketType;
        return $this;
    }

    public function getNumberOfTickets(): ?int
    {
        return $this->numberOfTickets;
    }

    public function setNumberOfTickets(int $numberOfTickets): static
    {
        $this->numberOfTickets = $numberOfTickets;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
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

    public function setConfirmationNumber(string $confirmationNumber): static
    {
        $this->confirmationNumber = $confirmationNumber;
        return $this;
    }

    // Business Logic Methods

    public function isConfirmed(): bool
    {
        return $this->status === 'CONFIRMED';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isFreeTicket(): bool
    {
        return floatval($this->totalPrice) == 0;
    }

    public function getStatusEmoji(): string
    {
        return match ($this->status) {
            'CONFIRMED' => '✅',
            'PENDING' => '⏳',
            'CANCELLED' => '❌',
            default => '❓',
        };
    }

    public function getTicketMultiplier(): float
    {
        return $this->ticketType === 'VIP' ? 1.5 : 1.0;
    }

    public function getFormattedConfirmationNumber(): string
    {
        return 'REG-' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
    }
}