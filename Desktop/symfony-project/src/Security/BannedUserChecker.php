<?php
// src/Security/BannedUserChecker.php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BannedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBanned()) {
            $banMessage = 'Votre compte a été banni. ';
            if ($user->getBanReason()) {
                $banMessage .= 'Raison: ' . $user->getBanReason() . '. ';
            }
            if ($user->getBannedUntil()) {
                $banMessage .= 'Le bannissement expire le: ' . $user->getBannedUntil()->format('d/m/Y H:i');
            } else {
                $banMessage .= 'Il s\'agit d\'un bannissement permanent.';
            }
            throw new CustomUserMessageAuthenticationException($banMessage);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Vérification après authentification (si nécessaire)
    }
}