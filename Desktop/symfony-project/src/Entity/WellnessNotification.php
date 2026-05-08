<?php

namespace App\Entity;

use App\Repository\WellnessNotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WellnessNotificationRepository::class)]
#[ORM\Table(name: 'wellness_notification')]
#[ORM\HasLifecycleCallbacks]
class WellnessNotification
{
    public const TYPE_DAILY_MOOD_REMINDER = 'daily_mood';
    public const TYPE_GOAL_DEADLINE_REMINDER = 'goal_deadline';
    public const TYPE_WEEKLY_SUMMARY = 'weekly_summary';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fingerprint = null;

    #[ORM\Column]
    private ?bool $isRead = false;

    #[ORM\Column]
    private ?bool $emailSent = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailSentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $emailError = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isRead = false;
        $this->emailSent = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function isEmailSent(): ?bool
    {
        return $this->emailSent;
    }

    public function setEmailSent(bool $emailSent): static
    {
        $this->emailSent = $emailSent;
        return $this;
    }

    public function getEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->emailSentAt;
    }

    public function setEmailSentAt(?\DateTimeImmutable $emailSentAt): static
    {
        $this->emailSentAt = $emailSentAt;
        return $this;
    }

    public function getEmailError(): ?string
    {
        return $this->emailError;
    }

    public function setEmailError(?string $emailError): static
    {
        $this->emailError = $emailError;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
