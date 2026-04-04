<?php

namespace App\Service;

class CVSummary
{
    private string $firstname = '';
    private string $lastname = '';
    private string $phone = '';
    private string $email = '';
    private string $dateofbirth = '';

    public function getFirstname(): string { return $this->firstname; }
    public function setFirstname(string $firstname): self { $this->firstname = $firstname; return $this; }
    public function getLastname(): string { return $this->lastname; }
    public function setLastname(string $lastname): self { $this->lastname = $lastname; return $this; }
    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = $phone; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getDateofbirth(): string { return $this->dateofbirth; }
    public function setDateofbirth(string $dateofbirth): self { $this->dateofbirth = $dateofbirth; return $this; }
    public function toArray(): array {
        return [
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'phone' => $this->phone,
            'email' => $this->email,
            'dateofbirth' => $this->dateofbirth,
        ];
    }
}