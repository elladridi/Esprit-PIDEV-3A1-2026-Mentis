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
            'anxiety'  => 'anxiety relief breathing exercises calm meditation',
            'stress'   => 'stress relief relaxation music nature sounds',
            'wellness' => 'mindfulness meditation wellness self care',
            'sleep'    => 'sleep meditation relaxing music bedtime',
            default    => 'mental health relaxation therapy guided meditation',
        };

        $lower = strtolower($riskLevel);
        if (str_contains($lower, 'high') || str_contains($lower, 'severe') || str_contains($lower, 'critical')) {
            $base .= ' crisis support mental health';
        }

        return $base;
    }

    public function fetchVideos(string $assessmentType, string $riskLevel, int $maxResults = 6): array
{
    $query = $this->buildQuery($assessmentType, $riskLevel);
    $url   = 'https://www.googleapis.com/youtube/v3/search'
        . '?part=snippet'
        . '&type=video'
        . '&videoEmbeddable=true'
        . '&safeSearch=strict'
        . '&relevanceLanguage=en'
        . '&maxResults=' . $maxResults
        . '&q=' . urlencode($query)
        . '&key=' . $this->apiKey;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log for debugging
    error_log('[YouTube] Status: ' . $status . ' | Query: ' . $query);
    error_log('[YouTube] cURL error: ' . $curlError);

    if ($status !== 200 || !$result) {
        error_log('[YouTube] Failed - status: ' . $status . ' error: ' . $curlError);
        return [];
    }

    $data    = json_decode($result, true);
    $items   = $data['items'] ?? [];

    error_log('[YouTube] Items found: ' . count($items));

    if (isset($data['error'])) {
        error_log('[YouTube] API error: ' . json_encode($data['error']));
        return [];
    }

    $videos = [];

    foreach ($items as $item) {
        $videoId = $item['id']['videoId'] ?? '';
        if (empty($videoId)) continue;

        $snippet    = $item['snippet'] ?? [];
        $thumbnails = $snippet['thumbnails'] ?? [];
        $thumbnail  = $thumbnails['high']['url']
            ?? $thumbnails['medium']['url']
            ?? $thumbnails['default']['url']
            ?? '';

        $videos[] = [
            'videoId'      => $videoId,
            'title'        => $snippet['title'] ?? 'Untitled',
            'channelTitle' => $snippet['channelTitle'] ?? '',
            'thumbnail'    => $thumbnail,
            'watchUrl'     => 'https://www.youtube.com/watch?v=' . $videoId,
        ];
    }

    error_log('[YouTube] Videos built: ' . count($videos));
    return $videos;
}
}