<?php

namespace App\Service;

use App\Entity\Goal;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoalAIService
{
    private string $geminiApiKey;
    private string $geminiUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $geminiApiKey
    ) {
        $this->geminiApiKey = $geminiApiKey;
        $this->geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';
    }

    public function getGoalAdvice(Goal $goal): string
    {
        if (empty($this->geminiApiKey)) {
            return "AI advice is currently unavailable (missing API key).";
        }

        $prompt = sprintf(
            "You are a professional life coach and mental health assistant. 
            Analyze this goal and provide short, practical advice in 3-4 bullet points.
            Goal Title: %s
            Goal Description: %s
            Deadline: %s
            Status: %s",
            $goal->getTitle(),
            $goal->getDescription(),
            $goal->getDeadline()?->format('Y-m-d') ?? 'No deadline set',
            $goal->isCompleted() ? 'Completed' : 'Pending'
        );

        try {
            $response = $this->httpClient->request('POST', $this->geminiUrl . '?key=' . $this->geminiApiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            return "Could not generate AI advice at this moment.";

        } catch (\Exception $e) {
            $this->logger->error('Gemini AI Error: ' . $e->getMessage());
            return "An error occurred while communicating with the AI assistant.";
        }
    }
}
