<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class SpotifyService
{
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    private string $tokenUrl = 'https://accounts.spotify.com/api/token';
    private string $searchUrl = 'https://api.spotify.com/v1/search';

    public function __construct(
        string $clientId,
        string $clientSecret,
        ?LoggerInterface $logger = null
    ) {
        $this->clientId = trim($clientId);
        $this->clientSecret = trim($clientSecret);
        $this->logger = $logger;
    }

    private ?LoggerInterface $logger;

    // ─────────────────────────────────────────────────────
    // ACCESS TOKEN
    // ─────────────────────────────────────────────────────
    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $ch = curl_init($this->tokenUrl);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'grant_type' => 'client_credentials',
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \RuntimeException(curl_error($ch));
            }

            curl_close($ch);

            $data = json_decode($result, true);

            $this->accessToken = $data['access_token'] ?? null;

            return $this->accessToken;
        } catch (\Exception $e) {
            $this->logError('Spotify Auth Error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ─────────────────────────────────────────────────────
    // PLAYLIST RECOMMENDATIONS
    // ─────────────────────────────────────────────────────
    public function fetchPlaylists(
        string $assessmentType,
        string $riskLevel,
        int $limit = 6
    ): array {
        $token = $this->getAccessToken();

        if (!$token) {
            return $this->getFallbackPlaylists($assessmentType);
        }

        $queries = $this->buildQueries($assessmentType, $riskLevel);
        $playlists = [];

        foreach ($queries as $query) {
            if (count($playlists) >= $limit) {
                break;
            }

            try {
                $url = $this->searchUrl
                    . '?q=' . urlencode($query)
                    . '&type=playlist'
                    . '&limit=2'
                    . '&market=US';

                $ch = curl_init($url);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $token,
                        'Content-Type: application/json',
                    ],
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ]);

                $result = curl_exec($ch);

                if (curl_errno($ch)) {
                    throw new \RuntimeException(curl_error($ch));
                }

                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if ($status !== 200) {
                    continue;
                }

                $data = json_decode($result, true);

                $items = $data['playlists']['items'] ?? [];

                foreach ($items as $item) {
                    if (count($playlists) >= $limit) {
                        break;
                    }

                    $id = $item['id'] ?? '';

                    $playlists[] = [
                        'id' => $id,
                        'name' => $item['name'] ?? 'Untitled Playlist',
                        'description' => strip_tags($item['description'] ?? ''),
                        'owner' => $item['owner']['display_name'] ?? 'Spotify',
                        'tracks' => $item['tracks']['total'] ?? 0,
                        'image' => $item['images'][0]['url']
                            ?? $item['images'][1]['url']
                            ?? null,
                        'url' => $item['external_urls']['spotify'] ?? '#',
                        'embedUrl' => $id
                            ? 'https://open.spotify.com/embed/playlist/' . $id
                            : null,
                    ];
                }
            } catch (\Exception $e) {
                $this->logError('Spotify Playlist Search Error', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $playlists ?: $this->getFallbackPlaylists($assessmentType);
    }

    // ─────────────────────────────────────────────────────
    // TRACK RECOMMENDATIONS
    // ─────────────────────────────────────────────────────
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
            $url = $this->searchUrl
                . '?q=' . urlencode($query)
                . '&type=track'
                . '&limit=1'
                . '&market=US';

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \RuntimeException(curl_error($ch));
            }

            curl_close($ch);

            $data = json_decode($result, true);

            $track = $data['tracks']['items'][0] ?? null;

            if ($track) {
                return [
                    'track_name' => $track['name'] ?? 'Unknown Track',
                    'artist_name' => $track['artists'][0]['name'] ?? 'Unknown Artist',
                    'track_url' => $track['external_urls']['spotify'] ?? '#',
                    'preview_url' => $track['preview_url'] ?? null,
                    'image_url' => $track['album']['images'][0]['url'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            $this->logError('Spotify Track Search Error', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getFallbackRecommendation($mood);
    }

    // ─────────────────────────────────────────────────────
    // SEARCH QUERY BUILDER
    // ─────────────────────────────────────────────────────
    private function buildQueries(string $assessmentType, string $riskLevel): array
    {
        $type = strtolower($assessmentType);
        $risk = strtolower($riskLevel);

        $isHigh = in_array($risk, ['high', 'severe'], true);

        return match ($type) {
            'depression' => $isHigh
                ? ['depression recovery music', 'uplifting therapy playlist', 'healing sad mood']
                : ['feel good music playlist', 'happy mood boost', 'positive energy music'],

            'anxiety' => $isHigh
                ? ['anxiety relief calm music', 'panic relief meditation music', '432hz calm anxiety']
                : ['relaxing background music', 'calm focus music', 'stress relief instrumental'],

            'stress' => [
                'stress relief music playlist',
                'relaxation music nature sounds',
                'calming piano music',
            ],

            'sleep' => [
                'sleep music relaxation',
                'deep sleep meditation music',
                'sleep sounds white noise',
            ],

            'wellness' => [
                'mindfulness meditation music',
                'positive energy wellness',
                'morning motivation music',
            ],

            default => [
                'mental health relaxation music',
                'calm mindfulness playlist',
                'emotional healing music',
            ],
        };
    }

    // ─────────────────────────────────────────────────────
    // FALLBACK PLAYLISTS
    // ─────────────────────────────────────────────────────
    private function getFallbackPlaylists(string $assessmentType): array
    {
        $type = strtolower($assessmentType);

        $fallbacks = [
            'anxiety' => [
                [
                    'name' => 'Peaceful Piano',
                    'description' => 'Relaxing piano music',
                    'owner' => 'Spotify',
                    'tracks' => 172,
                    'image' => null,
                    'url' => 'https://open.spotify.com/playlist/37i9dQZF1DX4sWSpwq3LiO',
                    'embedUrl' => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX4sWSpwq3LiO',
                ],
            ],

            'depression' => [
                [
                    'name' => 'Mood Booster',
                    'description' => 'Feel-good uplifting songs',
                    'owner' => 'Spotify',
                    'tracks' => 100,
                    'image' => null,
                    'url' => 'https://open.spotify.com/playlist/37i9dQZF1DX3rxVfibe1L0',
                    'embedUrl' => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX3rxVfibe1L0',
                ],
            ],

            'default' => [
                [
                    'name' => 'Feeling Good',
                    'description' => 'Positive and relaxing music',
                    'owner' => 'Spotify',
                    'tracks' => 85,
                    'image' => null,
                    'url' => 'https://open.spotify.com/playlist/37i9dQZF1DX3rxVfibe1L0',
                    'embedUrl' => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX3rxVfibe1L0',
                ],
            ],
        ];

        return $fallbacks[$type] ?? $fallbacks['default'];
    }

    // ─────────────────────────────────────────────────────
    // FALLBACK TRACKS
    // ─────────────────────────────────────────────────────
    private function getFallbackRecommendation(string $mood): array
    {
        $fallbacks = [
            'happy' => [
                'track_name' => 'Walking On Sunshine',
                'artist_name' => 'Katrina & The Waves',
                'track_url' => 'https://open.spotify.com/search/Walking%20On%20Sunshine',
                'preview_url' => null,
                'image_url' => null,
            ],

            'sad' => [
                'track_name' => 'Weightless',
                'artist_name' => 'Marconi Union',
                'track_url' => 'https://open.spotify.com/search/Weightless',
                'preview_url' => null,
                'image_url' => null,
            ],

            'neutral' => [
                'track_name' => 'Lo-fi Study',
                'artist_name' => 'Lofi Girl',
                'track_url' => 'https://open.spotify.com/search/lofi',
                'preview_url' => null,
                'image_url' => null,
            ],
        ];

        return $fallbacks[$mood] ?? $fallbacks['neutral'];
    }

    // ─────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────
    public function isConfigured(): bool
    {
        return !empty($this->clientId)
            && !empty($this->clientSecret);
    }

    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error('[SpotifyService] ' . $message, $context);
        }
    }
}