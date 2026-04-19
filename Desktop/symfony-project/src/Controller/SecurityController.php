<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $userRepository): Response
    {
        // If already logged in, redirect to home page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Check if the user trying to login is banned
        if ($lastUsername) {
            $user = $userRepository->findOneBy(['email' => $lastUsername]);
            if ($user && $user->isBanned()) {
                $banMessage = 'Your account has been banned. ';
                if ($user->getBanReason()) {
                    $banMessage .= 'Reason: ' . $user->getBanReason() . '. ';
                }
                if ($user->getBannedUntil()) {
                    $banMessage .= 'Ban expires on: ' . $user->getBannedUntil()->format('Y-m-d H:i');
                } else {
                    $banMessage .= 'This is a permanent ban.';
                }
                $error = new CustomUserMessageAuthenticationException($banMessage);
            }
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}