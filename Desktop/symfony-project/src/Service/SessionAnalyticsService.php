<?php

namespace App\Service;

use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SessionAnalyticsService
{
    private SessionRepository $sessionRepo;
    private UserRepository $userRepo;
    private EntityManagerInterface $em;

    public function __construct(
        SessionRepository $sessionRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ) {
        $this->sessionRepo = $sessionRepo;
        $this->userRepo = $userRepo;
        $this->em = $em;
    }

    public function getSessionAnalytics(): array
    {
        $sessions = $this->sessionRepo->findAll();
        
        $analytics = [
            'total_sessions' => count($sessions),
            'by_status' => $this->getSessionsByStatus($sessions),
            'by_type' => $this->getSessionsByType($sessions),
            'by_category' => $this->getSessionsByCategory($sessions),
            'reservations' => $this->getReservationStats($sessions),
            'popularity' => $this->getPopularityStats($sessions),
            'revenue' => $this->getRevenueStats($sessions),
            'monthly_trend' => $this->getMonthlyTrend($sessions),
            'top_sessions' => $this->getTopSessions($sessions),
            'patient_stats' => $this->getPatientStats(),
        ];
        
        return $analytics;
    }

    private function getSessionsByStatus(array $sessions): array
    {
        $stats = [
            'scheduled' => 0,
            'active' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        
        foreach ($sessions as $session) {
            $status = $session->getStatus();
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }
        
        return $stats;
    }

    private function getSessionsByType(array $sessions): array
    {
        $stats = [];
        
        foreach ($sessions as $session) {
            $type = $session->getSessionType();
            if (!isset($stats[$type])) {
                $stats[$type] = 0;
            }
            $stats[$type]++;
        }
        
        arsort($stats);
        return $stats;
    }

    private function getSessionsByCategory(array $sessions): array
    {
        $stats = [];
        
        foreach ($sessions as $session) {
            $category = $session->getCategory() ?? 'General';
            if (!isset($stats[$category])) {
                $stats[$category] = 0;
            }
            $stats[$category]++;
        }
        
        arsort($stats);
        return $stats;
    }

    private function getReservationStats(array $sessions): array
    {
        $totalReservations = 0;
        $completedReservations = 0;
        $cancelledReservations = 0;
        $uniquePatients = [];
        
        foreach ($sessions as $session) {
            if ($session->getReservedBy()) {
                $totalReservations++;
                $uniquePatients[$session->getReservedBy()] = true;
                
                if ($session->getStatus() === 'completed') {
                    $completedReservations++;
                } elseif ($session->getStatus() === 'cancelled') {
                    $cancelledReservations++;
                }
            }
        }
        
        return [
            'total' => $totalReservations,
            'completed' => $completedReservations,
            'cancelled' => $cancelledReservations,
            'unique_patients' => count($uniquePatients),
            'completion_rate' => $totalReservations > 0 
                ? round(($completedReservations / $totalReservations) * 100, 1) 
                : 0,
        ];
    }

    private function getPopularityStats(array $sessions): array
    {
        $totalPopularity = 0;
        $maxPopularity = 0;
        $mostPopularSession = null;
        
        foreach ($sessions as $session) {
            $popularity = $session->getPopularity();
            $totalPopularity += $popularity;
            
            if ($popularity > $maxPopularity) {
                $maxPopularity = $popularity;
                $mostPopularSession = $session;
            }
        }
        
        return [
            'total' => $totalPopularity,
            'average' => count($sessions) > 0 ? round($totalPopularity / count($sessions), 1) : 0,
            'max' => $maxPopularity,
            'most_popular' => $mostPopularSession,
        ];
    }

    private function getRevenueStats(array $sessions): array
    {
        $totalRevenue = 0;
        $completedRevenue = 0;
        
        foreach ($sessions as $session) {
            $price = floatval($session->getPrice());
            
            if ($session->getReservedBy()) {
                $totalRevenue += $price;
                
                if ($session->getStatus() === 'completed') {
                    $completedRevenue += $price;
                }
            }
        }
        
        return [
            'total' => $totalRevenue,
            'completed' => $completedRevenue,
            'pending' => $totalRevenue - $completedRevenue,
            'average_per_session' => count($sessions) > 0 ? round($totalRevenue / count($sessions), 2) : 0,
        ];
    }

    private function getMonthlyTrend(array $sessions): array
    {
        $months = [];
        
        foreach ($sessions as $session) {
            $date = $session->getSessionDate();
            if ($date) {
                $monthKey = $date->format('Y-m');
                if (!isset($months[$monthKey])) {
                    $months[$monthKey] = [
                        'month' => $date->format('F Y'),
                        'total' => 0,
                        'completed' => 0,
                        'reserved' => 0,
                    ];
                }
                $months[$monthKey]['total']++;
                
                if ($session->getStatus() === 'completed') {
                    $months[$monthKey]['completed']++;
                }
                
                if ($session->getReservedBy()) {
                    $months[$monthKey]['reserved']++;
                }
            }
        }
        
        return array_reverse($months);
    }

    private function getTopSessions(array $sessions): array
    {
        $sorted = $sessions;
        usort($sorted, function($a, $b) {
            return $b->getPopularity() <=> $a->getPopularity();
        });
        
        return array_slice($sorted, 0, 5);
    }

    private function getPatientStats(): array
    {
        $patients = $this->userRepo->findBy(['type' => 'Patient']);
        
        $activePatients = 0;
        $totalReservations = 0;
        
        foreach ($patients as $patient) {
            $patientSessions = $this->sessionRepo->findBy(['reservedBy' => $patient->getId()]);
            if (count($patientSessions) > 0) {
                $activePatients++;
                $totalReservations += count($patientSessions);
            }
        }
        
        return [
            'total_patients' => count($patients),
            'active_patients' => $activePatients,
            'inactive_patients' => count($patients) - $activePatients,
            'avg_reservations_per_patient' => $activePatients > 0 
                ? round($totalReservations / $activePatients, 1) 
                : 0,
        ];
    }
}