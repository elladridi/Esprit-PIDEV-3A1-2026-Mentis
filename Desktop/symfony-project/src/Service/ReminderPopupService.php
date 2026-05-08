<?php

namespace App\Service;

use App\Entity\Session;

class ReminderPopupService
{
    private WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getReminderData(Session $session, \DateTimeInterface $currentDate): array
    {
        $sessionDate = $session->getSessionDate();
        $startTime = $session->getStartTime();
        $location = $session->getLocation();
        
        if (!$sessionDate) {
            return ['show_popup' => false];
        }
        
        $interval = $currentDate->diff($sessionDate);
        $daysLeft = (int)$interval->format('%r%a');
        
        $weather = $this->weatherService->getWeatherForLocation($location, $sessionDate);
        
        $isToday = $sessionDate->format('Y-m-d') === $currentDate->format('Y-m-d');
        $isUpcoming = $daysLeft >= 0 && $daysLeft <= 3;
        
        return [
            'show_popup' => ($isToday || $isUpcoming),
            'is_today' => $isToday,
            'days_left' => $daysLeft,
            'session_title' => $session->getTitle(),
            'session_date' => $sessionDate->format('l, F j, Y'),
            'session_time' => $startTime ? $startTime->format('H:i') : 'TBD',
            'location' => $location,
            'weather' => $weather,
            'reminder_message' => $this->getReminderMessage($isToday, $daysLeft, $location),
        ];
    }

    private function getReminderMessage(bool $isToday, int $daysLeft, string $location): string
    {
        if ($isToday) {
            return "🎯 Your session is TODAY! Don't forget to attend at {$location}.";
        } elseif ($daysLeft === 1) {
            return "📅 Your session is TOMORROW! Get ready at {$location}.";
        } elseif ($daysLeft === 2) {
            return "📅 Your session is in 2 days! We're excited to see you at {$location}.";
        } elseif ($daysLeft === 3) {
            return "📅 Your session is in 3 days! Prepare yourself at {$location}.";
        }
        
        return "📅 You have an upcoming session. Don't forget!";
    }
}