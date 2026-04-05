<?php

namespace App\Service;

class GeolocationService
{
    public static function isCriticalRisk(string $riskLevel): bool
    {
        $lower = strtolower($riskLevel);
        return str_contains($lower, 'severe')
            || str_contains($lower, 'critical')
            || str_contains($lower, 'high')
            || str_contains($lower, 'emergency');
    }
}