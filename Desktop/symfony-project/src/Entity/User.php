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

    #[ORM\Column(length: 100)]
    private string $dateofbirth = '';

    #[ORM\Column(length: 50)]
    private string $type = 'Patient';

    #[ORM\Column(length: 100, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 1000)]
    private string $password = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceData = null;

    #[ORM\Column(type: 'boolean')]
    private bool $faceEnabled = false;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters with null checks
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

    public function getDateofbirth(): string
    {
        return $this->dateofbirth;
    }

    public function setDateofbirth(?string $dateofbirth): self
    {
        $this->dateofbirth = $dateofbirth ?? '';
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

    public function getAge(): ?int
    {
        if (!$this->dateofbirth) {
            return null;
        }
        
        try {
            $birthDate = new \DateTime($this->dateofbirth);
            $today = new \DateTime();
            return $today->diff($birthDate)->y;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

   public function getRoles(): array
{
    $roles = ['ROLE_USER'];
    
    // Don't convert to lowercase - check the actual value
    $type = trim($this->type ?? '');
    
    // Debug - log the actual type
    error_log("User type from database: '" . $type . "'");
    
    // Check for exact matches (case-sensitive)
    if ($type === 'Admin' || $type === 'admin') {
        $roles[] = 'ROLE_ADMIN';
        $roles[] = 'ROLE_PSYCHOLOGIST';
        $roles[] = 'ROLE_PATIENT';
    } elseif ($type === 'Psychologist' || $type === 'psychologist') {
        $roles[] = 'ROLE_PSYCHOLOGIST';
        $roles[] = 'ROLE_PATIENT';
    } elseif ($type === 'Patient' || $type === 'patient') {
        $roles[] = 'ROLE_PATIENT';
    }
    
    error_log("Assigned roles: " . implode(', ', $roles));
    
    return array_unique($roles);
}
    public function eraseCredentials(): void
    {
    }
    // Add this method to your User entity class
public function getFullName(): string
{
    return trim($this->firstname . ' ' . $this->lastname);
}
}