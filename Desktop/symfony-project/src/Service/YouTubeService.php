<?php

namespace App\Service;

class YouTubeService
{
    private string $apiKey = 'AIzaSyBpLEeIXjXyD4sS8vehVJ_VKATHwntd3Bk';

    public function buildQuery(string $assessmentType, string $riskLevel): string
    {
        $base = match (strtolower($assessmentType)) {
            'depression' => str_contains(strtolower($riskLevel), 'high') || str_contains(strtolower($riskLevel), 'severe')
                ? 'depression relief guided meditation therapy'
                : 'uplifting music for depression mood boost',
            'anxiety' => 'anxiety relief breathing exercises calm meditation',
            'stress' => 'stress relief relaxation music nature sounds',
            'wellness' => 'mindfulness meditation wellness self care',
            'sleep' => 'sleep meditation relaxing music bedtime',
            default => 'mental health relaxation therapy guided meditation',
        };

        $lower = strtolower($riskLevel);

        if (str_contains($lower, 'high') || str_contains($lower, 'severe') || str_contains($lower, 'critical')) {
            $base .= ' crisis support mental health';
        }

        return $base;
    }

    /**
     * @return array<int, array{videoId: string, title: string, channelTitle: string, thumbnail: string, watchUrl: string}>
     */
    public function fetchVideos(string $assessmentType, string $riskLevel, int $maxResults = 6): array
    {
        $query = $this->buildQuery($assessmentType, $riskLevel);
        $url = 'https://www.googleapis.com/youtube/v3/search'
            . '?part=snippet'
            . '&type=video'
            . '&videoEmbeddable=true'
            . '&safeSearch=strict'
            . '&relevanceLanguage=en'
            . '&maxResults=' . $maxResults
            . '&q=' . urlencode($query)
            . '&key=' . $this->apiKey;

        $ch = curl_init($url);

        if ($ch === false) {
            return [];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log('[YouTube] Status: ' . $status . ' | Query: ' . $query);
        error_log('[YouTube] cURL error: ' . $curlError);

        if ($status !== 200 || !is_string($result)) {
            return [];
        }

        $data = json_decode($result, true);

        if (!is_array($data)) {
            return [];
        }

        if (isset($data['error'])) {
            error_log('[YouTube] API error: ' . json_encode($data['error']));
            return [];
        }

        $items = $data['items'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        $videos = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? [];
            $videoId = is_array($id) ? ($id['videoId'] ?? '') : '';

            if (!is_string($videoId) || $videoId === '') {
                continue;
            }

            $snippet = $item['snippet'] ?? [];
            $thumbnails = is_array($snippet) ? ($snippet['thumbnails'] ?? []) : [];
            $thumbnail = '';

            if (is_array($thumbnails)) {
                $thumbnail = $thumbnails['high']['url']
                    ?? $thumbnails['medium']['url']
                    ?? $thumbnails['default']['url']
                    ?? '';
            }

            $videos[] = [
                'videoId' => $videoId,
                'title' => is_array($snippet) ? (string) ($snippet['title'] ?? 'Untitled') : 'Untitled',
                'channelTitle' => is_array($snippet) ? (string) ($snippet['channelTitle'] ?? '') : '',
                'thumbnail' => (string) $thumbnail,
                'watchUrl' => 'https://www.youtube.com/watch?v=' . $videoId,
            ];
        }

        return $videos;
    }
}
