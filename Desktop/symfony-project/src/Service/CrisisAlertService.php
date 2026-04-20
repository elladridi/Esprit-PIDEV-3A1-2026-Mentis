<?php
// src/Service/CrisisAlertService.php

namespace App\Service;

use App\Entity\AssessmentResult;
use Symfony\Component\HttpKernel\KernelInterface;

class CrisisAlertService
{
    private string $storagePath;
    private const CRISIS_FILE = 'crisis_alerts.json';

    public function __construct(KernelInterface $kernel)
    {
        $this->storagePath = $kernel->getProjectDir() . '/var/crisis_alerts/';
        
        // Create directory if it doesn't exist
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    private function getAllAlertsFromFile(): array
    {
        $filePath = $this->storagePath . self::CRISIS_FILE;
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        $alerts = json_decode($content, true);
        
        return is_array($alerts) ? $alerts : [];
    }

    private function saveAlertsToFile(array $alerts): void
    {
        $filePath = $this->storagePath . self::CRISIS_FILE;
        file_put_contents($filePath, json_encode($alerts, JSON_PRETTY_PRINT));
    }

    private function getSeenAlertsForPsychologist(int $psychologistId): array
    {
        $filePath = $this->storagePath . 'seen_' . $psychologistId . '.json';
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        $seen = json_decode($content, true);
        
        return is_array($seen) ? $seen : [];
    }

    private function saveSeenAlertsForPsychologist(int $psychologistId, array $seenIds): void
    {
        $filePath = $this->storagePath . 'seen_' . $psychologistId . '.json';
        file_put_contents($filePath, json_encode($seenIds, JSON_PRETTY_PRINT));
    }

    public function addCrisisAlert(AssessmentResult $result): void
    {
        $alerts = $this->getAllAlertsFromFile();
        
        $alertId = $result->getResultId();
        
        // Check if already exists
        foreach ($alerts as $alert) {
            if ($alert['id'] === $alertId) {
                return;
            }
        }
        
        $patientName = 'Unknown Patient';
        if ($result->getUser()) {
            $patientName = $result->getUser()->getFirstname() . ' ' . $result->getUser()->getLastname();
        }
        
        $assessmentTitle = 'Unknown Assessment';
        if ($result->getAssessment()) {
            $assessmentTitle = $result->getAssessment()->getTitle();
        }
        
        $alert = [
            'id' => $alertId,
            'patient_name' => $patientName,
            'patient_id' => $result->getUser()?->getId(),
            'risk_level' => $result->getRiskLevel(),
            'total_score' => $result->getTotalScore(),
            'assessment_title' => $assessmentTitle,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'timestamp' => time(),
        ];
        
        array_unshift($alerts, $alert);
        
        // Keep only last 100 alerts
        $alerts = array_slice($alerts, 0, 100);
        
        $this->saveAlertsToFile($alerts);
    }

    public function getAllAlertsForPsychologist(int $psychologistId): array
    {
        $allAlerts = $this->getAllAlertsFromFile();
        $seenIds = $this->getSeenAlertsForPsychologist($psychologistId);
        
        // Add is_seen flag for this psychologist
        $alertsWithStatus = [];
        foreach ($allAlerts as $alert) {
            $alert['is_seen'] = in_array($alert['id'], $seenIds);
            $alertsWithStatus[] = $alert;
        }
        
        return $alertsWithStatus;
    }

    public function getUnreadCountForPsychologist(int $psychologistId): int
    {
        $allAlerts = $this->getAllAlertsFromFile();
        $seenIds = $this->getSeenAlertsForPsychologist($psychologistId);
        
        $unreadCount = 0;
        foreach ($allAlerts as $alert) {
            if (!in_array($alert['id'], $seenIds)) {
                $unreadCount++;
            }
        }
        
        return $unreadCount;
    }

    public function getUnreadAlertsForPsychologist(int $psychologistId): array
    {
        $allAlerts = $this->getAllAlertsFromFile();
        $seenIds = $this->getSeenAlertsForPsychologist($psychologistId);
        
        $unread = [];
        foreach ($allAlerts as $alert) {
            if (!in_array($alert['id'], $seenIds)) {
                $alert['is_seen'] = false;
                $unread[] = $alert;
            }
        }
        
        return $unread;
    }

    public function markAsRead(int $psychologistId, int $alertId): void
    {
        $seenIds = $this->getSeenAlertsForPsychologist($psychologistId);
        
        if (!in_array($alertId, $seenIds)) {
            $seenIds[] = $alertId;
            $this->saveSeenAlertsForPsychologist($psychologistId, $seenIds);
        }
    }

    public function markAllAsRead(int $psychologistId): void
    {
        $allAlerts = $this->getAllAlertsFromFile();
        $seenIds = [];
        
        foreach ($allAlerts as $alert) {
            $seenIds[] = $alert['id'];
        }
        
        $this->saveSeenAlertsForPsychologist($psychologistId, $seenIds);
    }

    public function clearAllAlerts(): void
    {
        $filePath = $this->storagePath . self::CRISIS_FILE;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}