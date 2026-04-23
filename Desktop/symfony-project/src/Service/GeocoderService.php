<?php

namespace App\Service;

use App\Entity\Session;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocoderService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getCoordinates(string $address): ?array
    {
        try {
            // Use OpenStreetMap Nominatim API (100% FREE, no API key needed)
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ],
                'headers' => [
                    'User-Agent' => 'MentisApp/1.0'
                ]
            ]);
            
            $data = $response->toArray();
            
            if (count($data) > 0) {
                return [
                    'lat' => floatval($data[0]['lat']),
                    'lng' => floatval($data[0]['lon']),
                    'formatted_address' => $data[0]['display_name'] ?? $address
                ];
            }
            
            // Fallback coordinates (Tunis city center)
            return [
                'lat' => 36.8065,
                'lng' => 10.1815,
                'formatted_address' => $address . ' (approximate location)'
            ];
            
        } catch (\Exception $e) {
            return [
                'lat' => 36.8065,
                'lng' => 10.1815,
                'formatted_address' => $address . ' (location approximate)'
            ];
        }
    }

    public function getSessionLocation(Session $session): ?array
    {
        $address = $session->getLocation();
        if (empty($address)) {
            return null;
        }
        
        return $this->getCoordinates($address);
    }
}