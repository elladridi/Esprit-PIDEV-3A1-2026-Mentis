<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class BookRecommendationService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['BIGBOOK_API_KEY'] ?? '';
    }

    public function getRecommendations(string $userType): array
    {
        $query = $this->getQueryForUserType($userType);

        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
                'query' => [
                    'q' => $query,
                    'maxResults' => 10,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['items'])) {
                return array_map(function ($item) {
                    $volumeInfo = $item['volumeInfo'] ?? [];
                    $imageLinks = $volumeInfo['imageLinks'] ?? [];
                    
                    // Prefer larger images if available, fallback to thumbnail
                    $image = $imageLinks['medium'] ?? $imageLinks['small'] ?? $imageLinks['thumbnail'] ?? '';
                    
                    // Force HTTPS for images to avoid mixed content issues
                    if ($image && str_starts_with($image, 'http:')) {
                        $image = str_replace('http:', 'https:', $image);
                    }

                    return [
                        'title' => $volumeInfo['title'] ?? 'Unknown Title',
                        'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : 'Unknown Author',
                        'image' => $image,
                        'description' => $volumeInfo['description'] ?? 'No description available.',
                        'link' => $volumeInfo['infoLink'] ?? $volumeInfo['canonicalVolumeLink'] ?? '#',
                    ];
                }, $data['items']);
            }

            return $this->getMockRecommendations($userType);
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | \JsonException $e) {
            // Return mock data if API fails
            return $this->getMockRecommendations($userType);
        }
    }

    private function getQueryForUserType(string $userType): string
    {
        return $userType === 'psychologist' ? 'advanced psychology' : 'psychology for beginners';
    }

    private function getMockRecommendations(string $userType): array
    {
        if ($userType === 'psychologist') {
            return [
                [
                    'title' => 'Clinical Psychology: Science, Practice, and Culture',
                    'author' => 'Andrew M. Pomerantz',
                    'image' => 'https://example.com/clinical-psychology.jpg',
                    'description' => 'A comprehensive textbook on clinical psychology covering assessment, diagnosis, and treatment approaches.',
                    'link' => 'https://example.com/clinical-psychology',
                ],
                [
                    'title' => 'Abnormal Psychology',
                    'author' => 'Ronald J. Comer',
                    'image' => 'https://example.com/abnormal-psychology.jpg',
                    'description' => 'An in-depth exploration of psychological disorders and their treatments.',
                    'link' => 'https://example.com/abnormal-psychology',
                ],
                [
                    'title' => 'Cognitive Behavioral Therapy: Basics and Beyond',
                    'author' => 'Judith S. Beck',
                    'image' => 'https://example.com/cbt.jpg',
                    'description' => 'A practical guide to cognitive behavioral therapy techniques for mental health professionals.',
                    'link' => 'https://example.com/cbt',
                ],
            ];
        } else {
            return [
                [
                    'title' => 'The Happiness Advantage',
                    'author' => 'Shawn Achor',
                    'image' => 'https://example.com/happiness-advantage.jpg',
                    'description' => 'Learn how positive psychology can improve your life and work performance.',
                    'link' => 'https://example.com/happiness-advantage',
                ],
                [
                    'title' => 'Feeling Good: The New Mood Therapy',
                    'author' => 'David D. Burns',
                    'image' => 'https://example.com/feeling-good.jpg',
                    'description' => 'A self-help book introducing cognitive behavioral techniques for overcoming depression.',
                    'link' => 'https://example.com/feeling-good',
                ],
                [
                    'title' => 'The Power of Habit',
                    'author' => 'Charles Duhigg',
                    'image' => 'https://example.com/power-of-habit.jpg',
                    'description' => 'Understanding how habits work and how to change them for better mental health.',
                    'link' => 'https://example.com/power-of-habit',
                ],
            ];
        }
    }
}