<?php
// src/Security/LoginFailureListener.php

namespace App\Security;

use App\Entity\LoginAttempt;
use App\Repository\LoginAttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(event: LoginFailureEvent::class, method: 'onAuthenticationFailure')]
#[AsEventListener(event: InteractiveLoginEvent::class, method: 'onAuthenticationSuccess')]
class LoginFailureListener
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private LoginAttemptRepository $loginAttemptRepo;
    private const MAX_ATTEMPTS = 3;
    private const BLOCK_DURATION = 15;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        LoginAttemptRepository $loginAttemptRepo
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->loginAttemptRepo = $loginAttemptRepo;
    }

    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }
        
        $email = $request->request->get('_username', '');
        $ipAddress = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');

        // Record failed attempt
        $attempt = new LoginAttempt();
        $attempt->setEmail($email)
            ->setIpAddress($ipAddress ?? 'unknown')
            ->setAttemptedAt(new \DateTime())
            ->setWasSuccessful(false)
            ->setUserAgent($userAgent ?? 'unknown');

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        // Check if should block
        $failedCount = $this->loginAttemptRepo->countRecentFailedAttempts($email, $ipAddress ?? 'unknown', self::BLOCK_DURATION);

        if ($failedCount >= self::MAX_ATTEMPTS) {
            $request->getSession()->set('login_blocked_until', new \DateTime("+" . self::BLOCK_DURATION . " minutes"));
            $request->getSession()->set('login_blocked_email', $email);
        }
    }

    public function onAuthenticationSuccess(InteractiveLoginEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }
        
        $user = $event->getAuthenticationToken()->getUser();
        $email = $user->getUserIdentifier();
        $ipAddress = $request->getClientIp();

        // Record successful attempt
        $attempt = new LoginAttempt();
        $attempt->setEmail($email)
            ->setIpAddress($ipAddress ?? 'unknown')
            ->setAttemptedAt(new \DateTime())
            ->setWasSuccessful(true)
            ->setUserAgent($request->headers->get('User-Agent') ?? 'unknown');

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        // Clear block if exists
        $request->getSession()->remove('login_blocked_until');
        $request->getSession()->remove('login_blocked_email');
    }
}