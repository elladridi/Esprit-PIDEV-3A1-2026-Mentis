<?php
// src/Service/BadgeService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\AssessmentResultRepository;
use Doctrine\ORM\EntityManagerInterface;

class BadgeService
{
    public function __construct(
        private AssessmentResultRepository $resultRepo,
        private EntityManagerInterface $em
    ) {}

    public function getUserBadges(User $user): array
    {
        $badges = [];

        // Get assessment count
        $assessmentCount = $this->resultRepo->count(['user' => $user]);
        
        // Get session count - adjust based on your actual entity
        // If you have a Session entity, uncomment this:
        // $sessionCount = $this->sessionRepo->count(['patient' => $user]);
        // For now, set to 0 or calculate from your data
        $sessionCount = 0; // Update this when you have session entity
        
        $daysSinceJoin = (new \DateTime())->diff($user->getCreatedAt())->days;

        // 🌱 Newcomer — always earned
        $badges[] = [
            'id' => 'newcomer',
            'name' => 'Newcomer',
            'description' => 'Welcome to Mentis!',
            'icon' => '🌱',
            'color' => '#50C878',
            'earned' => true,
            'type' => 'welcome',
        ];

        // 📅 Loyal Member
        $badges[] = [
            'id' => 'loyal',
            'name' => 'Loyal Member',
            'description' => 'Member for 30+ days',
            'icon' => '📅',
            'color' => '#4A90E2',
            'earned' => $daysSinceJoin >= 30,
            'progress' => min(100, round(($daysSinceJoin / 30) * 100)),
            'required' => 'Be a member for 30 days',
            'type' => 'milestone',
        ];

        // 🔐 Security Pro
        $badges[] = [
            'id' => 'security_pro',
            'name' => 'Security Pro',
            'description' => 'Enabled Face ID login',
            'icon' => '🔐',
            'color' => '#9B59B6',
            'earned' => $user->isFaceEnabled(),
            'required' => 'Enable Face ID in your profile',
            'type' => 'security',
        ];

        // 📋 First Steps
        $badges[] = [
            'id' => 'first_steps',
            'name' => 'First Steps',
            'description' => 'Completed your first assessment',
            'icon' => '📋',
            'color' => '#E67E22',
            'earned' => $assessmentCount >= 1,
            'progress' => min(100, $assessmentCount * 100),
            'required' => 'Complete 1 assessment',
            'type' => 'assessment',
        ];

        // 🔬 Explorer
        $badges[] = [
            'id' => 'explorer',
            'name' => 'Explorer',
            'description' => 'Completed 5 assessments',
            'icon' => '🔬',
            'color' => '#1ABC9C',
            'earned' => $assessmentCount >= 5,
            'progress' => min(100, round(($assessmentCount / 5) * 100)),
            'required' => 'Complete 5 assessments',
            'type' => 'assessment',
        ];

        // 🏆 Dedicated
        $badges[] = [
            'id' => 'dedicated',
            'name' => 'Dedicated',
            'description' => 'Completed 10 assessments',
            'icon' => '🏆',
            'color' => '#F1C40F',
            'earned' => $assessmentCount >= 10,
            'progress' => min(100, round(($assessmentCount / 10) * 100)),
            'required' => 'Complete 10 assessments',
            'type' => 'assessment',
        ];

        // 🧠 Mental Health Champion
        $badges[] = [
            'id' => 'champion',
            'name' => 'Mental Health Champion',
            'description' => 'Completed 10+ assessments',
            'icon' => '🧠',
            'color' => '#2C3E50',
            'earned' => $assessmentCount >= 10,
            'progress' => min(100, round(($assessmentCount / 10) * 100)),
            'required' => 'Complete 10 assessments',
            'type' => 'assessment',
        ];

        // 👑 Grand Master
        $badges[] = [
            'id' => 'grand_master',
            'name' => 'Grand Master',
            'description' => 'Completed 20+ assessments',
            'icon' => '👑',
            'color' => '#D35400',
            'earned' => $assessmentCount >= 20,
            'progress' => min(100, round(($assessmentCount / 20) * 100)),
            'required' => 'Complete 20 assessments',
            'type' => 'assessment',
        ];

        return $badges;
    }

    public function getEarnedCount(User $user): int
    {
        return count(array_filter($this->getUserBadges($user), fn($b) => $b['earned']));
    }

    public function getTotalBadges(): int
    {
        return count($this->getUserBadges(new User()));
    }

    public function getLatestEarnedBadges(User $user, int $limit = 3): array
    {
        $badges = array_filter($this->getUserBadges($user), fn($b) => $b['earned']);
        return array_slice($badges, 0, $limit);
    }
}