<?php

namespace App\Service;

use App\Entity\Event;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIEventService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        $this->apiKey = $_ENV['GROQ_EVENT_API_KEY'] ?? $_ENV['GROQ_API_KEY'] ?? '';
        $this->model = $_ENV['GROQ_EVENT_MODEL'] ?? 'llama-3.1-8b-instant';
        $this->apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    }

    public function generateEventDescription(
        string $title,
        string $type,
        string $audience = 'patients',
        string $goals = ''
    ): array {
        // Check if API key is available
        if (!$this->apiKey) {
            // Return a dummy description if no API key
            return $this->getDummyDescription($title, $type);
        }

        $prompt = <<<PROMPT
Generate a professional event description for a mental health platform.

Event title: {$title}
Event type: {$type}
Target audience: {$audience}
Goals: {$goals}

Requirements:
- Write in a warm, professional tone.
- Keep it between 100 and 150 words.
- Mention benefits for participants.
- Avoid making medical promises.
- Return only the description text.
PROMPT;

        $result = $this->askAI($prompt, 0.7, 350, 'description');
        
        // If AI request failed, return dummy description
        if (!$result['success']) {
            return $this->getDummyDescription($title, $type);
        }
        
        return $result;
    }

    /**
     * Generate a dummy description when API is not available
     */
    private function getDummyDescription(string $title, string $type): array
    {
        // Convert type to readable format
        $readableType = match($type) {
            'WORKSHOP' => 'workshop',
            'GROUP_THERAPY' => 'group therapy session',
            'SEMINAR' => 'seminar',
            'SOCIAL' => 'social event',
            default => 'event',
        };
        
        $description = "Join us for \"{$title}\"! This {$readableType} is designed to help participants improve their mental wellness and emotional resilience. " .
                       "You will learn practical strategies, connect with others in a safe and supportive environment, and leave feeling empowered and inspired. " .
                       "Don't miss this opportunity to invest in your mental health journey with MENTIS. Spaces are limited, so register today!";
        
        return [
            'success' => true,
            'description' => $description,
            'error' => null,
        ];
    }

    public function suggestEventIdeas(string $topic, string $audience = 'patients'): array
    {
        if (!$this->apiKey) {
            return $this->getDummyIdeas($topic);
        }

        $prompt = <<<PROMPT
Suggest 5 mental health event ideas for this topic: {$topic}

Audience: {$audience}

For each idea, include:
- title
- event type
- short description
- suggested duration

Return as clear bullet points.
PROMPT;

        $result = $this->askAI($prompt, 0.8, 700, 'ideas');
        
        if (!$result['success']) {
            return $this->getDummyIdeas($topic);
        }
        
        return $result;
    }

    /**
     * Generate dummy ideas when API is not available
     */
    private function getDummyIdeas(string $topic): array
    {
        $ideas = "• Title: {$topic} Workshop\n  Type: Workshop\n  Description: An interactive workshop exploring {$topic}\n  Duration: 2 hours\n\n" .
                 "• Title: {$topic} Support Group\n  Type: Group Therapy\n  Description: A safe space to discuss {$topic}\n  Duration: 90 minutes\n\n" .
                 "• Title: Understanding {$topic}\n  Type: Seminar\n  Description: Educational seminar about {$topic}\n  Duration: 3 hours\n\n" .
                 "• Title: {$topic} Social Connect\n  Type: Social\n  Description: Connect with others interested in {$topic}\n  Duration: 2 hours\n\n" .
                 "• Title: {$topic} Mindfulness Session\n  Type: Workshop\n  Description: Mindfulness practices focused on {$topic}\n  Duration: 1 hour";
        
        return [
            'success' => true,
            'ideas' => $ideas,
            'error' => null,
        ];
    }

    public function generateEventAgenda(
        string $title,
        string $type,
        int $durationMinutes = 120
    ): array {
        if (!$this->apiKey) {
            return $this->getDummyAgenda($title, $durationMinutes);
        }

        $prompt = <<<PROMPT
Create a structured agenda for a mental health event.

Title: {$title}
Type: {$type}
Duration: {$durationMinutes} minutes

Return:
- welcome/introduction
- main activities
- discussion/reflection
- closing
- suggested timing for each part
PROMPT;

        $result = $this->askAI($prompt, 0.6, 700, 'agenda');
        
        if (!$result['success']) {
            return $this->getDummyAgenda($title, $durationMinutes);
        }
        
        return $result;
    }

    /**
     * Generate dummy agenda when API is not available
     */
    private function getDummyAgenda(string $title, int $durationMinutes): array
    {
        $welcomeMinutes = round($durationMinutes * 0.1);
        $mainMinutes = round($durationMinutes * 0.5);
        $breakMinutes = round($durationMinutes * 0.1);
        $discussionMinutes = round($durationMinutes * 0.2);
        $closingMinutes = round($durationMinutes * 0.1);
        
        $agenda = "Welcome & Introduction: {$welcomeMinutes} minutes\n" .
                  "Main Activities: {$mainMinutes} minutes\n" .
                  "Break: {$breakMinutes} minutes\n" .
                  "Discussion & Reflection: {$discussionMinutes} minutes\n" .
                  "Closing & Q&A: {$closingMinutes} minutes";
        
        return [
            'success' => true,
            'agenda' => $agenda,
            'error' => null,
        ];
    }

    public function summarizeEventPerformance(Event $event, int $registrations, float $revenue): array
    {
        if (!$this->apiKey) {
            return $this->getDummySummary($event, $registrations, $revenue);
        }

        $prompt = <<<PROMPT
Analyze this event performance for an admin dashboard.

Title: {$event->getTitle()}
Type: {$event->getEventType()}
Status: {$event->getStatus()}
Participants: {$event->getCurrentParticipants()} / {$event->getMaxParticipants()}
Registrations: {$registrations}
Revenue: {$revenue}

Return:
- short performance summary
- strengths
- possible issues
- 3 improvement suggestions
PROMPT;

        $result = $this->askAI($prompt, 0.5, 700, 'summary');
        
        if (!$result['success']) {
            return $this->getDummySummary($event, $registrations, $revenue);
        }
        
        return $result;
    }

    /**
     * Generate dummy summary when API is not available
     */
    private function getDummySummary(Event $event, int $registrations, float $revenue): array
    {
        $occupancy = $event->getMaxParticipants() > 0 
            ? round(($event->getCurrentParticipants() / $event->getMaxParticipants()) * 100) 
            : 0;
        
        $summary = "Event '{$event->getTitle()}' has {$registrations} registrations with {$revenue} revenue. " .
                   "Occupancy rate is {$occupancy}%. ";
        
        if ($occupancy > 70) {
            $summary .= "Strong attendance! Consider increasing capacity for future events.";
        } elseif ($occupancy > 40) {
            $summary .= "Good attendance. Continue promoting similar events.";
        } else {
            $summary .= "Low attendance. Consider adjusting marketing strategy or timing.";
        }
        
        return [
            'success' => true,
            'summary' => $summary,
            'error' => null,
        ];
    }

    private function askAI(string $prompt, float $temperature, int $maxTokens, string $fieldName): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an AI assistant specialized in mental health event planning for the MENTIS platform. Keep responses safe, professional, and practical.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                return [
                    'success' => false,
                    $fieldName => '',
                    'error' => 'No valid AI response.',
                ];
            }

            return [
                'success' => true,
                $fieldName => trim($data['choices'][0]['message']['content']),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                $fieldName => '',
                'error' => 'AI request failed: ' . $e->getMessage(),
            ];
        }
    }
}