<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Change this line to use WEATHERAPI_KEY
        $this->apiKey = $_ENV['WEATHERAPI_KEY'] ?? '';
    }

    public function getWeatherForLocation(string $location, \DateTimeInterface $date): ?array
    {
        if (empty($this->apiKey)) {
            return $this->getFallbackWeather();
        }

        try {
            // Get forecast from WeatherAPI (not OpenWeatherMap)
            $response = $this->httpClient->request('GET', 'https://api.weatherapi.com/v1/forecast.json', [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => $location,
                    'days' => 5,
                    'aqi' => 'no',
                    'alerts' => 'no',
                ]
            ]);
            
            $data = $response->toArray();
            
            // Find forecast for the session date
            $sessionDateStr = $date->format('Y-m-d');
            
            foreach ($data['forecast']['forecastday'] as $forecast) {
                if ($forecast['date'] === $sessionDateStr) {
                    return [
                        'temperature' => round($forecast['day']['avgtemp_c']),
                        'feels_like' => round($forecast['day']['avgtemp_c']),
                        'description' => $forecast['day']['condition']['text'],
                        'icon' => $forecast['day']['condition']['icon'],
                        'humidity' => $forecast['day']['avghumidity'],
                        'wind_speed' => $forecast['day']['maxwind_kph'],
                    ];
                }
            }
            
            return $this->getFallbackWeather();
            
        } catch (\Exception $e) {
            return $this->getFallbackWeather();
        }
    }

    private function getFallbackWeather(): array
    {
        return [
            'temperature' => '--',
            'feels_like' => '--',
            'description' => 'Weather data currently unavailable',
            'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png',
            'humidity' => '--',
            'wind_speed' => '--',
        ];
    }
}