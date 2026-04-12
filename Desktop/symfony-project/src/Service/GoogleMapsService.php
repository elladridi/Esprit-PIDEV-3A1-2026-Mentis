<?php

namespace App\Service;

class GoogleMapsService
{
    private ?string $apiKey;

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    public function getStaticMapUrl(string $location, int $width = 400, int $height = 250, int $zoom = 15): ?string
    {
        if (!$this->apiKey) {
            return null;
        }

        $encodedLocation = urlencode($location);
        
        return sprintf(
            'https://maps.googleapis.com/maps/api/staticmap?center=%s&zoom=%d&size=%dx%d&markers=color:red%%7C%s&key=%s',
            $encodedLocation,
            $zoom,
            $width,
            $height,
            $encodedLocation,
            $this->apiKey
        );
    }

    public function getDirectionsUrl(string $location): string
    {
        return 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($location);
    }

    public function getSearchUrl(string $location): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($location);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== null && !empty($this->apiKey);
    }
}