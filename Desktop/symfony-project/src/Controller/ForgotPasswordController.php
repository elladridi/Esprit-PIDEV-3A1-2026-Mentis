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
use Symfony\Component\Uid\Uuid;

class ForgotPasswordController extends AbstractController
{
    // Stockage temporaire des tokens
    private static array $resetTokens = [];

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token unique
                $token = Uuid::v4()->toRfc4122();
                
                // Stocker le token
                self::$resetTokens[$token] = [
                    'email' => $email,
                    'expires_at' => time() + 3600
                ];

                // Nettoyer les anciens tokens
                foreach (self::$resetTokens as $key => $data) {
                    if ($data['expires_at'] < time()) {
                        unset(self::$resetTokens[$key]);
                    }
                }

                // Envoyer l'email
                $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], 0);
                
                $emailMessage = (new Email())
                    ->from('noreply@mentis.com')
                    ->to($user->getEmail())
                    ->subject('Reset Your Mentis Password')
                    ->html($this->renderView('email/reset_password.html.twig', [
                        'user' => $user,
                        'resetLink' => $resetLink,
                    ]));

                $mailer->send($emailMessage);

                $this->addFlash('success', 'Password reset link has been sent to your email.');
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
        // Vérifier si le token existe
        if (!isset(self::$resetTokens[$token]) || self::$resetTokens[$token]['expires_at'] < time()) {
            $this->addFlash('error', 'Invalid or expired password reset link.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $email = self::$resetTokens[$token]['email'];
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
                
                unset(self::$resetTokens[$token]);

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