<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyService
{
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $clientId,
        string $clientSecret
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'] ?? null;
            return $this->accessToken;
        } catch (\Exception $e) {
            $this->logger->error('Spotify Auth Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getRecommendationForMood(string $mood): array
    {
        $query = match ($mood) {
            'very_happy' => 'upbeat feel good pop hits',
            'happy' => 'happy chill pop',
            'neutral' => 'chill focus indie',
            'sad' => 'comforting acoustic calm',
            'very_sad' => 'soothing peaceful healing',
            default => 'chill study music',
        };

        $token = $this->getAccessToken();
        if (!$token) {
            return $this->getFallbackRecommendation($mood);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.spotify.com/v1/search', [
                'query' => [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 1,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            $data = $response->toArray();
            $track = $data['tracks']['items'][0] ?? null;

            if ($track && isset($track['external_urls']['spotify']) && str_contains($track['external_urls']['spotify'], '/track/')) {
                $imageUrl = $track['album']['images'][0]['url'] ?? null;
                return [
                    'track_name' => $track['name'],
                    'artist_name' => $track['artists'][0]['name'] ?? 'Unknown Artist',
                    'track_url' => $track['external_urls']['spotify'],
                    'preview_url' => $track['preview_url'] ?? null,
                    'image_url' => $imageUrl,
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Spotify Search Error: ' . $e->getMessage());
        }

        return $this->getFallbackRecommendation($mood);
    }

    private function getFallbackRecommendation(string $mood): array
    {
        // Curated fallbacks using SEARCH URLs to prevent geo-blocking 404s
        $fallbacks = [
            'very_happy' => [
                'track_name' => 'Happy',
                'artist_name' => 'Pharrell Williams',
                'track_url' => 'https://open.spotify.com/search/Happy%20Pharrell%20Williams',
                'preview_url' => null,
                'image_url' => 'https://i.scdn.co/image/ab67616d0000b2733989390637f9035f8d5e1657',
            ],
            'happy' => [
                'track_name' => 'Walking On Sunshine',
                'artist_name' => 'Katrina & The Waves',
                'track_url' => 'https://open.spotify.com/search/Walking%20On%20Sunshine%20Katrina',
                'preview_url' => null,
                'image_url' => 'https://i.scdn.co/image/ab67616d0000b273e045b410940f81d8975a5c13',
            ],
            'neutral' => [
                'track_name' => 'Lo-fi Study',
                'artist_name' => 'Lofi Girl',
                'track_url' => 'https://open.spotify.com/search/lofi%20study%20girl',
                'preview_url' => null,
                'image_url' => 'https://i.scdn.co/image/ab67616d0000b273c68388484f295e548232c96c',
            ],
            'sad' => [
                'track_name' => 'Someone Like You',
                'artist_name' => 'Adele',
                'track_url' => 'https://open.spotify.com/search/Someone%20Like%20You%20Adele',
                'preview_url' => null,
                'image_url' => 'https://i.scdn.co/image/ab67616d0000b273212f6ab30588663806a6c429',
            ],
            'very_sad' => [
                'track_name' => 'Weightless',
                'artist_name' => 'Marconi Union',
                'track_url' => 'https://open.spotify.com/search/Weightless%20Marconi%20Union',
                'preview_url' => null,
                'image_url' => 'https://i.scdn.co/image/ab67616d0000b27376a26778f3f878939a039757',
            ],
        ];

        return $fallbacks[$mood] ?? $fallbacks['neutral'];
    }
}
