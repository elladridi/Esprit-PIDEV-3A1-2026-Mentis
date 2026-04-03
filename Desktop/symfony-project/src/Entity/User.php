<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
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

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getDateofbirth(): string
    {
        return $this->dateofbirth;
    }

    public function setDateofbirth(string $dateofbirth): self
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

    public function setEmail(string $email): self
    {
        $this->email = $email;
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
        $type = strtolower($this->type);

        if ($type === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        } elseif ($type === 'psychologist') {
            $roles[] = 'ROLE_PSYCHOLOGIST';
        }

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }
}
