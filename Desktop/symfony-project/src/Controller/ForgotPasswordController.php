<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ForgotPasswordController extends AbstractController
{
    // Persistent storage (temporary file)
    private static array $resetTokens = [];
    private static string $tokenFile = __DIR__ . '/../../var/tokens.json';

    private function saveTokens(): void
    {
        file_put_contents(self::$tokenFile, json_encode(self::$resetTokens));
    }

    private function loadTokens(): void
    {
        if (file_exists(self::$tokenFile)) {
            self::$resetTokens = json_decode(file_get_contents(self::$tokenFile), true) ?: [];
        }
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, MailerInterface $mailer): Response
    {
        $this->loadTokens();

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                
                // Expiration in 1 hour (3600 seconds)
                $expiresAt = time() + 3600;
                
                self::$resetTokens[$token] = [
                    'email' => $email,
                    'expires_at' => $expiresAt
                ];
                
                $this->saveTokens();

                $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], 0);
                
                // Clean up expired tokens
                foreach (self::$resetTokens as $key => $data) {
                    if ($data['expires_at'] < time()) {
                        unset(self::$resetTokens[$key]);
                    }
                }
                $this->saveTokens();

                $emailMessage = (new Email())
                    ->from('noreply@mentis.com')
                    ->to($user->getEmail())
                    ->subject('Reset Your Mentis Password')
                    ->html($this->renderView('email/reset_password.html.twig', [
                        'user' => $user,
                        'resetLink' => $resetLink,
                    ]));

                $mailer->send($emailMessage);

                $this->addFlash('success', 'A password reset link has been sent to your email.');
            } else {
                $this->addFlash('success', 'If an account exists with this email, a reset link has been sent.');
            }

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $this->loadTokens();

        // Check if token exists
        if (!isset(self::$resetTokens[$token])) {
            $this->addFlash('error', 'Invalid link. Please make a new request.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $tokenData = self::$resetTokens[$token];
        
        // Check if token has expired
        if ($tokenData['expires_at'] < time()) {
            unset(self::$resetTokens[$token]);
            $this->saveTokens();
            $this->addFlash('error', 'This link has expired (valid for 1 hour). Please make a new request.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $email = $tokenData['email'];
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->flush();
                
                // Delete used token
                unset(self::$resetTokens[$token]);
                $this->saveTokens();

                $this->addFlash('success', 'Password reset successfully! Please login.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token,
            'email' => $email
        ]);
    }
}