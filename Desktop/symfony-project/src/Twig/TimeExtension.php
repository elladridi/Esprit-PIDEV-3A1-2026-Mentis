<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('time_ago', [$this, 'calculateTimeAgo']),
        ];
    }

    public function calculateTimeAgo(?\DateTimeInterface $date): string
    {
        if (null === $date) {
            return 'Unknown';
        }

        $now = new \DateTime();
        $interval = $now->diff($date);

        if ($interval->y > 0) {
            return $interval->y . ' yr' . ($interval->y > 1 ? 's' : '') . ' ago';
        }

        if ($interval->m > 0) {
            return $interval->m . ' mo' . ($interval->m > 1 ? 's' : '') . ' ago';
        }

        if ($interval->d > 0) {
            return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
        }

        if ($interval->h > 0) {
            return $interval->h . ' hr' . ($interval->h > 1 ? 's' : '') . ' ago';
        }

        if ($interval->i > 0) {
            return $interval->i . ' min' . ($interval->i > 1 ? 's' : '') . ' ago';
        }

        return 'just now';
    }
}
