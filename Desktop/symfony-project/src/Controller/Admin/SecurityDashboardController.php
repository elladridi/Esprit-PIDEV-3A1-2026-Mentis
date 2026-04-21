<?php
// src/Controller/Admin/SecurityDashboardController.php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\LoginAttemptRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\BanNotificationService;


#[Route('/admin/security')]
#[IsGranted('ROLE_ADMIN')]
class SecurityDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_security_dashboard', methods: ['GET'])]
    public function dashboard(LoginAttemptRepository $loginAttemptRepo, UserRepository $userRepo): Response
    {
        // Get statistics
        $totalAttempts = $loginAttemptRepo->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $failedAttempts = $loginAttemptRepo->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.wasSuccessful = false')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $last24h = new \DateTime('-24 hours');
        $failedLast24h = $loginAttemptRepo->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.wasSuccessful = false')
            ->andWhere('la.attemptedAt >= :cutoff')
            ->setParameter('cutoff', $last24h)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $uniqueIps = $loginAttemptRepo->createQueryBuilder('la')
            ->select('COUNT(DISTINCT la.ipAddress)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Get suspicious accounts
        $cutoff = new \DateTime('-24 hours');
        $suspiciousResults = $loginAttemptRepo->createQueryBuilder('la')
            ->select('la.email, COUNT(la.id) as failed_count, MAX(la.attemptedAt) as last_attempt')
            ->where('la.attemptedAt >= :cutoff')
            ->andWhere('la.wasSuccessful = false')
            ->setParameter('cutoff', $cutoff)
            ->groupBy('la.email')
            ->having('COUNT(la.id) >= 3')
            ->orderBy('failed_count', 'DESC')
            ->getQuery()
            ->getResult();
        
        $suspiciousAccounts = [];
        foreach ($suspiciousResults as $result) {
            $suspiciousAccounts[] = [
                'email' => $result['email'],
                'failed_count' => $result['failed_count'],
                'last_attempt' => $result['last_attempt'] instanceof \DateTime ? $result['last_attempt']->format('Y-m-d H:i:s') : $result['last_attempt'],
            ];
        }
        
        // Get recent attempts
        $recentAttemptsData = $loginAttemptRepo->createQueryBuilder('la')
            ->orderBy('la.attemptedAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
        
        $recentAttempts = [];
        foreach ($recentAttemptsData as $attempt) {
            $recentAttempts[] = [
                'id' => $attempt->getId(),
                'email' => $attempt->getEmail(),
                'ipAddress' => $attempt->getIpAddress(),
                'attemptedAt' => $attempt->getAttemptedAt()->format('Y-m-d H:i:s'),
                'wasSuccessful' => $attempt->isWasSuccessful(),
            ];
        }
        
        // Get banned users for stats
        $bannedUsers = $userRepo->findBy(['isBanned' => true]);
        
        return $this->render('admin/security/dashboard.html.twig', [
            'stats' => [
                'total_attempts' => (int) $totalAttempts,
                'failed_attempts' => (int) $failedAttempts,
                'failed_last_24h' => (int) $failedLast24h,
                'unique_ips' => (int) $uniqueIps,
            ],
            'suspiciousAccounts' => $suspiciousAccounts,
            'recentAttempts' => $recentAttempts,
            'bannedUsers' => $bannedUsers,
        ]);
    }

    #[Route('/stats', name: 'admin_security_stats', methods: ['GET'])]
    public function getStats(LoginAttemptRepository $loginAttemptRepo): JsonResponse
    {
        try {
            $totalAttempts = $loginAttemptRepo->createQueryBuilder('la')
                ->select('COUNT(la.id)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            $failedAttempts = $loginAttemptRepo->createQueryBuilder('la')
                ->select('COUNT(la.id)')
                ->where('la.wasSuccessful = false')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            $last24h = new \DateTime('-24 hours');
            $failedLast24h = $loginAttemptRepo->createQueryBuilder('la')
                ->select('COUNT(la.id)')
                ->where('la.wasSuccessful = false')
                ->andWhere('la.attemptedAt >= :cutoff')
                ->setParameter('cutoff', $last24h)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            $uniqueIps = $loginAttemptRepo->createQueryBuilder('la')
                ->select('COUNT(DISTINCT la.ipAddress)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            return $this->json([
                'success' => true,
                'total_attempts' => (int) $totalAttempts,
                'failed_attempts' => (int) $failedAttempts,
                'failed_last_24h' => (int) $failedLast24h,
                'unique_ips' => (int) $uniqueIps,
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/suspicious', name: 'admin_security_suspicious', methods: ['GET'])]
    public function getSuspicious(LoginAttemptRepository $loginAttemptRepo): JsonResponse
    {
        try {
            $cutoff = new \DateTime('-24 hours');
            
            $results = $loginAttemptRepo->createQueryBuilder('la')
                ->select('la.email, COUNT(la.id) as failed_count, MAX(la.attemptedAt) as last_attempt')
                ->where('la.attemptedAt >= :cutoff')
                ->andWhere('la.wasSuccessful = false')
                ->setParameter('cutoff', $cutoff)
                ->groupBy('la.email')
                ->having('COUNT(la.id) >= 3')
                ->orderBy('failed_count', 'DESC')
                ->getQuery()
                ->getResult();
            
            $data = [];
            foreach ($results as $result) {
                $data[] = [
                    'email' => $result['email'],
                    'failed_count' => $result['failed_count'],
                    'last_attempt' => $result['last_attempt'] instanceof \DateTime ? $result['last_attempt']->format('Y-m-d H:i:s') : $result['last_attempt'],
                ];
            }
            
            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/recent-attempts', name: 'admin_security_recent', methods: ['GET'])]
    public function getRecentAttempts(LoginAttemptRepository $loginAttemptRepo): JsonResponse
    {
        try {
            $attempts = $loginAttemptRepo->createQueryBuilder('la')
                ->orderBy('la.attemptedAt', 'DESC')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();
            
            $data = [];
            foreach ($attempts as $attempt) {
                $data[] = [
                    'id' => $attempt->getId(),
                    'email' => $attempt->getEmail(),
                    'ipAddress' => $attempt->getIpAddress(),
                    'attemptedAt' => $attempt->getAttemptedAt()->format('Y-m-d H:i:s'),
                    'wasSuccessful' => $attempt->isWasSuccessful(),
                ];
            }
            
            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/ban/{email}', name: 'admin_ban_suspicious', methods: ['POST'])]
public function banSuspiciousAccount(
    string $email,
    UserRepository $userRepo,
    EntityManagerInterface $em,
    BanNotificationService $banNotifier
): JsonResponse {
    try {
        $email = urldecode($email);
        $user  = $userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found: ' . $email], 404);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['success' => false, 'message' => 'Cannot ban another admin'], 403);
        }

        $bannedUntil = new \DateTime('+7 days');
        $reason      = 'Suspicious activity detected - Multiple failed login attempts';

        $user->setIsBanned(true);
        $user->setBannedAt(new \DateTime());
        $user->setBannedUntil($bannedUntil);
        $user->setBanReason($reason);
        $em->flush();

        // ✅ Send ban email
        try {
            $banNotifier->sendBanNotification($user, $reason, $bannedUntil);
        } catch (\Exception $e) {
            // Log but don't fail the ban if email fails
            error_log('Ban email failed: ' . $e->getMessage());
        }

        return $this->json([
            'success'      => true,
            'message'      => 'User banned and notified by email until ' . $bannedUntil->format('Y-m-d H:i:s'),
            'user_name'    => $user->getFullName(),
            'banned_until' => $bannedUntil->format('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

   #[Route('/unban/{id}', name: 'admin_unban_user', methods: ['POST'])]
public function unbanUser(
    int $id,
    UserRepository $userRepo,
    EntityManagerInterface $em,
    BanNotificationService $banNotifier
): JsonResponse {
    try {
        $user = $userRepo->find($id);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->setIsBanned(false);
        $user->setBannedUntil(null);
        $em->flush();

        // ✅ Send unban email
        try {
            $banNotifier->sendUnbanNotification($user);
        } catch (\Exception $e) {
            error_log('Unban email failed: ' . $e->getMessage());
        }

        return $this->json([
            'success'   => true,
            'message'   => 'User unbanned and notified by email',
            'user_name' => $user->getFullName()
        ]);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }}

    #[Route('/ban-user', name: 'admin_ban_user', methods: ['POST'])]
public function banUser(
    Request $request,
    UserRepository $userRepo,
    EntityManagerInterface $em,
    BanNotificationService $banNotifier
): JsonResponse {
    try {
        $data  = json_decode($request->getContent(), true);
        $email = $data['email'] ?? $request->request->get('email');

        if (!$email) {
            return $this->json(['success' => false, 'message' => 'Email is required'], 400);
        }

        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found: ' . $email], 404);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['success' => false, 'message' => 'Cannot ban another admin'], 403);
        }

        $bannedUntil = new \DateTime('+7 days');
        $reason      = 'Suspicious activity detected - Multiple failed login attempts';

        $user->setIsBanned(true);
        $user->setBannedAt(new \DateTime());
        $user->setBannedUntil($bannedUntil);
        $user->setBanReason($reason);
        $em->flush();

        // ✅ Send ban email
        try {
            $banNotifier->sendBanNotification($user, $reason, $bannedUntil);
        } catch (\Exception $e) {
            error_log('Ban email failed: ' . $e->getMessage());
        }

        return $this->json([
            'success'      => true,
            'message'      => 'User banned and notified by email until ' . $bannedUntil->format('Y-m-d H:i:s'),
            'user_name'    => $user->getFullName(),
            'banned_until' => $bannedUntil->format('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    #[Route('/banned-users', name: 'admin_banned_users', methods: ['GET'])]
    public function getBannedUsers(UserRepository $userRepo): JsonResponse
    {
        try {
            $bannedUsers = $userRepo->findBy(['isBanned' => true]);
            
            $data = [];
            foreach ($bannedUsers as $user) {
                $data[] = [
                    'id' => $user->getId(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail(),
                    'bannedAt' => $user->getBannedAt() ? $user->getBannedAt()->format('Y-m-d H:i:s') : null,
                    'bannedUntil' => $user->getBannedUntil() ? $user->getBannedUntil()->format('Y-m-d H:i:s') : 'Permanent',
                    'banReason' => $user->getBanReason(),
                ];
            }
            
            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/clear-old-attempts', name: 'admin_clear_attempts', methods: ['POST'])]
    public function clearOldAttempts(LoginAttemptRepository $loginAttemptRepo): JsonResponse
    {
        try {
            $cutoff = new \DateTime('-30 days');
            
            $deleted = $loginAttemptRepo->createQueryBuilder('la')
                ->delete()
                ->where('la.attemptedAt < :cutoff')
                ->setParameter('cutoff', $cutoff)
                ->getQuery()
                ->execute();
            
            return $this->json([
                'success' => true,
                'message' => "Deleted {$deleted} old login attempts",
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}