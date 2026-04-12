<?php

namespace App\Service;

class SpotifyService
{
    private string $clientId;
    private string $clientSecret;
    private string $tokenUrl  = 'https://accounts.spotify.com/api/token';
    private string $searchUrl = 'https://api.spotify.com/v1/search';

    public function __construct()
    {
        $this->clientId     = $_ENV['SPOTIFY_CLIENT_ID']     ?? '';
        $this->clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'] ?? '';
    }

    // ── Get access token ─────────────────────────────────
    private function getAccessToken(): ?string
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return null;
        }

        $ch = curl_init($this->tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'client_credentials',
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);
        return $data['access_token'] ?? null;
    }

    // ── Build search queries ─────────────────────────────
    private function buildQueries(string $assessmentType, string $riskLevel): array
    {
        $type   = strtolower($assessmentType);
        $risk   = strtolower($riskLevel);
        $isHigh = in_array($risk, ['high', 'severe']);

        return match($type) {
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

    // ── Fetch playlists from Spotify API ─────────────────
    public function fetchPlaylists(
        string $assessmentType,
        string $riskLevel,
        int $limit = 6
    ): array {
        $token = $this->getAccessToken();

        if (!$token) {
            return $this->getFallbackPlaylists($assessmentType, $riskLevel);
        }

        $queries   = $this->buildQueries($assessmentType, $riskLevel);
        $playlists = [];

        foreach ($queries as $query) {
            if (count($playlists) >= $limit) break;

            $url = $this->searchUrl
                . '?q=' . urlencode($query)
                . '&type=playlist'
                . '&limit=2'
                . '&market=US';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200) continue;

            $data  = json_decode($result, true);
            $items = $data['playlists']['items'] ?? [];

            foreach ($items as $item) {
                if (!$item || count($playlists) >= $limit) break;

                $image = $item['images'][0]['url']
                    ?? $item['images'][1]['url']
                    ?? null;

                $playlists[] = [
                    'id'          => $item['id']          ?? '',
                    'name'        => $item['name']         ?? 'Untitled Playlist',
                    'description' => strip_tags($item['description'] ?? ''),
                    'owner'       => $item['owner']['display_name'] ?? 'Spotify',
                    'tracks'      => $item['tracks']['total'] ?? 0,
                    'image'       => $image,
                    'url'         => $item['external_urls']['spotify'] ?? '#',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/' . ($item['id'] ?? ''),
                ];
            }
        }

        return $playlists ?: $this->getFallbackPlaylists($assessmentType, $riskLevel);
    }

    // ── Fallback playlists if API unavailable ────────────
    private function getFallbackPlaylists(string $assessmentType, string $riskLevel): array
    {
        $type = strtolower($assessmentType);

        $fallbacks = [
            'anxiety' => [
                [
                    'name'        => 'Peaceful Piano',
                    'description' => 'Relax and indulge with beautiful piano pieces',
                    'owner'       => 'Spotify',
                    'tracks'      => 172,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DX4sWSpwq3LiO',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX4sWSpwq3LiO',
                ],
                [
                    'name'        => 'Calm Vibes',
                    'description' => 'Chill out with these serene sounds',
                    'owner'       => 'Spotify',
                    'tracks'      => 60,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DWXe9gFZP0gtP',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DWXe9gFZP0gtP',
                ],
            ],
            'depression' => [
                [
                    'name'        => 'Mood Booster',
                    'description' => 'Get happy with today\'s dose of feel-good songs',
                    'owner'       => 'Spotify',
                    'tracks'      => 100,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DX3rxVfibe1L0',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX3rxVfibe1L0',
                ],
                [
                    'name'        => 'Life is Good',
                    'description' => 'Feel the positive energy',
                    'owner'       => 'Spotify',
                    'tracks'      => 80,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DX3rxVfibe1L0',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX3rxVfibe1L0',
                ],
            ],
            'sleep' => [
                [
                    'name'        => 'Sleep',
                    'description' => 'Gentle sounds for a restful night',
                    'owner'       => 'Spotify',
                    'tracks'      => 141,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DWZd79rJ6a7lp',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DWZd79rJ6a7lp',
                ],
            ],
            'stress' => [
                [
                    'name'        => 'Stress Relief',
                    'description' => 'Calming music for stressful moments',
                    'owner'       => 'Spotify',
                    'tracks'      => 90,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DWXe9gFZP0gtP',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DWXe9gFZP0gtP',
                ],
            ],
            'default' => [
                [
                    'name'        => 'Feeling Good',
                    'description' => 'Songs that make you feel better',
                    'owner'       => 'Spotify',
                    'tracks'      => 85,
                    'image'       => null,
                    'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DX3rxVfibe1L0',
                    'embedUrl'    => 'https://open.spotify.com/embed/playlist/37i9dQZF1DX3rxVfibe1L0',
                ],
            ],
        ];

        return $fallbacks[$type] ?? $fallbacks['default'];
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}