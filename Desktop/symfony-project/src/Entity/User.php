<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $firstname = '';

    #[ORM\Column(length: 50)]
    private string $lastname = '';

    #[ORM\Column(length: 50)]
    private string $phone = '';

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateofbirth = null;

    #[ORM\Column(length: 50)]
    private string $type = 'Patient';

    #[ORM\Column(length: 100, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 1000)]
    private string $password = '';

    // ==================== FACE RECOGNITION FIELDS ====================
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceData = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $faceEnabled = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $faceRegisteredAt = null;
    // ==================== END FACE RECOGNITION FIELDS ====================

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    // Ban fields
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isBanned = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedUntil = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $banReason = null;

    // Badge System Fields
    #[ORM\Column(name: 'total_booked_sessions', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $totalBookedSessions = 0;

    #[ORM\Column(name: 'discount_percent', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $discountPercent = 0;

    #[ORM\Column(name: 'free_session_available', type: 'boolean', nullable: true, options: ['default' => false])]
    private ?bool $freeSessionAvailable = false;

    #[ORM\Column(name: 'free_session_used', type: 'boolean', nullable: true, options: ['default' => false])]
    private ?bool $freeSessionUsed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->faceEnabled = false;
    }

    // ==================== BASIC GETTERS & SETTERS ====================
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname ?? '';
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname ?? '';
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone ?? '';
        return $this;
    }

    public function getDateofbirth(): ?\DateTimeInterface
    {
        return $this->dateofbirth;
    }

    public function setDateofbirth(?\DateTimeInterface $dateofbirth): self
    {
        $this->dateofbirth = $dateofbirth;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email ?? '';
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    // ==================== FACE RECOGNITION GETTERS & SETTERS ====================
    
    public function getFaceData(): ?string
    {
        return $this->faceData;
    }

    public function setFaceData(?string $faceData): self
    {
        $this->faceData = $faceData;
        return $this;
    }

    public function isFaceEnabled(): bool
    {
        return $this->faceEnabled;
    }

    public function setFaceEnabled(bool $faceEnabled): self
    {
        $this->faceEnabled = $faceEnabled;
        return $this;
    }

    public function getFaceRegisteredAt(): ?\DateTimeInterface
    {
        return $this->faceRegisteredAt;
    }

    public function setFaceRegisteredAt(?\DateTimeInterface $faceRegisteredAt): self
    {
        $this->faceRegisteredAt = $faceRegisteredAt;
        return $this;
    }

    /**
     * Get all face samples stored for this user
     * Returns an array of image paths or Base64 strings
     */
    public function getFaceSamples(): array
    {
        if (empty($this->faceData)) {
            return [];
        }
        
        // Check if it's JSON (multiple samples)
        $data = json_decode($this->faceData, true);
        if (is_array($data) && json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        
        // Old format: single sample as string
        return [$this->faceData];
    }

    /**
     * Store multiple face samples as JSON
     */
    public function setFaceSamples(array $samples): self
    {
        if (empty($samples)) {
            $this->faceData = null;
            $this->faceEnabled = false;
            $this->faceRegisteredAt = null;
        } else {
            $this->faceData = json_encode($samples);
        }
        return $this;
    }

    /**
     * Add a single face sample to the collection
     */
    public function addFaceSample(string $sample): self
    {
        $samples = $this->getFaceSamples();
        $samples[] = $sample;
        $this->faceData = json_encode($samples);
        return $this;
    }

    /**
     * Remove a specific face sample by index
     */
    public function removeFaceSample(int $index): self
    {
        $samples = $this->getFaceSamples();
        if (isset($samples[$index])) {
            unset($samples[$index]);
            $samples = array_values($samples);
            $this->setFaceSamples($samples);
        }
        return $this;
    }

    /**
     * Get the count of face samples
     */
    public function getFaceSamplesCount(): int
    {
        return count($this->getFaceSamples());
    }
    
    /**
     * Check if user has enough face samples (minimum 3)
     */
    public function hasEnoughFaceSamples(int $required = 3): bool
    {
        return $this->getFaceSamplesCount() >= $required;
    }
    
    /**
     * Get the first face sample path (for display purposes)
     */
    public function getFirstFaceSamplePath(): ?string
    {
        $samples = $this->getFaceSamples();
        if (empty($samples)) {
            return null;
        }
        return $samples[0];
    }
    
    /**
     * Get the last face sample (most recently added)
     */
    public function getLastFaceSample(): ?string
    {
        $samples = $this->getFaceSamples();
        if (empty($samples)) {
            return null;
        }
        return end($samples);
    }
    
    /**
     * Delete all face samples from filesystem
     * Call this before clearing the database references
     */
    public function deleteFaceSamplesFromFilesystem(string $projectDir): void
    {
        $samples = $this->getFaceSamples();
        foreach ($samples as $sample) {
            if (strpos($sample, 'uploads/faces/') === 0) {
                $fullPath = $projectDir . '/public/' . $sample;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
    }
    
    /**
     * Enable face recognition and set registration timestamp
     */
    public function enableFaceRecognition(): self
    {
        $this->faceEnabled = true;
        if ($this->faceRegisteredAt === null) {
            $this->faceRegisteredAt = new \DateTime();
        }
        return $this;
    }
    
    /**
     * Disable face recognition and clear registration timestamp
     */
    public function disableFaceRecognition(): self
    {
        $this->faceEnabled = false;
        $this->faceRegisteredAt = null;
        return $this;
    }
    
    // ==================== END FACE RECOGNITION ====================

    // ==================== BAN GETTERS & SETTERS ====================
    
    public function isBanned(): bool
    {
        // Check if ban has expired
        if ($this->isBanned && $this->bannedUntil && $this->bannedUntil < new \DateTime()) {
            $this->isBanned = false;
            $this->bannedUntil = null;
        }
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    public function getBannedAt(): ?\DateTimeInterface
    {
        return $this->bannedAt;
    }

    public function setBannedAt(?\DateTimeInterface $bannedAt): self
    {
        $this->bannedAt = $bannedAt;
        return $this;
    }

    public function getBannedUntil(): ?\DateTimeInterface
    {
        return $this->bannedUntil;
    }

    public function setBannedUntil(?\DateTimeInterface $bannedUntil): self
    {
        $this->bannedUntil = $bannedUntil;
        return $this;
    }

    public function getBanReason(): ?string
    {
        return $this->banReason;
    }

    public function setBanReason(?string $banReason): self
    {
        $this->banReason = $banReason;
        return $this;
    }
    
    // ==================== END BAN GETTERS & SETTERS ====================

    // ==================== BADGE SYSTEM GETTERS & SETTERS ====================

    public function getTotalBookedSessions(): ?int
    {
        return $this->totalBookedSessions;
    }

    public function setTotalBookedSessions(?int $totalBookedSessions): self
    {
        $this->totalBookedSessions = $totalBookedSessions;
        return $this;
    }

    public function getDiscountPercent(): ?int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(?int $discountPercent): self
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }

    public function isFreeSessionAvailable(): ?bool
    {
        return $this->freeSessionAvailable;
    }

    public function setFreeSessionAvailable(?bool $freeSessionAvailable): self
    {
        $this->freeSessionAvailable = $freeSessionAvailable;
        return $this;
    }

    public function isFreeSessionUsed(): ?bool
    {
        return $this->freeSessionUsed;
    }

    public function setFreeSessionUsed(?bool $freeSessionUsed): self
    {
        $this->freeSessionUsed = $freeSessionUsed;
        return $this;
    }

    public function incrementTotalBookedSessions(): self
    {
        $this->totalBookedSessions++;
        return $this;
    }

    public function getHighestBadge(): string
    {
        $badges = [
            200 => 'Wellness Guru',
            150 => 'Mentis Hero', 
            100 => 'Grand Master',
            75 => 'Master',
            50 => 'Legend',
            35 => 'Champion',
            20 => 'Dedicated',
            10 => 'Regular',
            5 => 'Explorer',
            1 => 'Newcomer'
        ];
        
        foreach ($badges as $sessions => $badgeName) {
            if ($this->totalBookedSessions >= $sessions) {
                return $badgeName;
            }
        }
        
        return 'Newcomer';
    }

    // ==================== END BADGE SYSTEM ====================

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * Calculate user's age from date of birth
     */
    public function getAge(): ?int
    {
        if (!$this->dateofbirth) {
            return null;
        }
        
        $today = new \DateTime();
        return $today->diff($this->dateofbirth)->y;
    }

    /**
     * Get formatted date of birth for display
     */
    public function getDateofbirthFormatted(): string
    {
        if (!$this->dateofbirth) {
            return '';
        }
        return $this->dateofbirth->format('Y-m-d');
    }

    /**
     * Get user's full name
     */
    public function getFullName(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return strtolower($this->type) === 'admin';
    }

    /**
     * Check if user is a psychologist
     */
    public function isPsychologist(): bool
    {
        return strtolower($this->type) === 'psychologist';
    }

    /**
     * Check if user is a patient
     */
    public function isPatient(): bool
    {
        return strtolower($this->type) === 'patient';
    }

    // ==================== SECURITY METHODS ====================
    
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        
        $type = trim($this->type ?? '');
        
        if ($type === 'Admin' || $type === 'admin') {
            $roles[] = 'ROLE_ADMIN';
            $roles[] = 'ROLE_PSYCHOLOGIST';
        } elseif ($type === 'Psychologist' || $type === 'psychologist') {
            $roles[] = 'ROLE_PSYCHOLOGIST';
        }
        
        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Clear sensitive data if needed
    }
}