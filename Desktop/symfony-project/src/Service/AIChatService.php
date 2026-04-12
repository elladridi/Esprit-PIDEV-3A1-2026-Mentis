<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class AIChatService
{
    private HttpClientInterface $httpClient;
    private string $groqApiKey;
    private string $groqModel;
    private string $groqUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->groqApiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $this->groqModel = $_ENV['GROQ_MODEL'] ?? 'llama-3.1-8b-instant';
        $this->groqUrl = 'https://api.groq.com/openai/v1/chat/completions';
    }

    /**
     * Send a message to the AI and get a response based on user type and context
     *
     * @param string $message The user's message
     * @param string $userType 'patient' or 'psychologist'
     * @param string|null $context Optional context/topic for the discussion
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function chat(string $message, string $userType = 'patient', ?string $context = null): array
    {
        if (empty($message)) {
            return [
                'success' => false,
                'message' => '',
                'error' => 'Message cannot be empty'
            ];
        }

        if (empty($this->groqApiKey)) {
            return [
                'success' => false,
                'message' => '',
                'error' => 'Groq API key is missing'
            ];
        }

        $systemPrompt = $this->buildSystemPrompt($userType, $context);

        try {
            $response = $this->httpClient->request('POST', $this->groqUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->groqModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            if (isset($data['choices'][0]['message']['content'])) {
                $aiMessage = trim($data['choices'][0]['message']['content']);
                return [
                    'success' => true,
                    'message' => $aiMessage,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'message' => '',
                'error' => 'No valid response from Groq AI'
            ];
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | \JsonException $e) {
            return [
                'success' => false,
                'message' => '',
                'error' => 'AI request failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate structured content (for psychologists only)
     *
     * @param string $topic The topic to generate content about
     * @param string $contentType Type of content: 'article', 'course', 'explanation'
     * @return array ['success' => bool, 'content' => [...], 'error' => string|null]
     */
    public function generateContent(string $topic, string $contentType = 'article'): array
    {
        if (empty($topic)) {
            return [
                'success' => false,
                'content' => [],
                'error' => 'Topic cannot be empty'
            ];
        }

        if (empty($this->groqApiKey)) {
            return [
                'success' => false,
                'content' => [],
                'error' => 'Groq API key is missing'
            ];
        }

        $systemPrompt = "You are an expert psychology educator. Generate well-structured, professional, and evidence-based content for a mental health platform. Format your response as valid JSON with the following structure: {\"title\": \"...\", \"sections\": [{\"heading\": \"...\", \"paragraphs\": [\"...\"]}]}";

        $userPrompt = "Generate a professional {$contentType} about: {$topic}\n\nRespond with valid JSON only.";

        try {
            $response = $this->httpClient->request('POST', $this->groqUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->groqModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            if (isset($data['choices'][0]['message']['content'])) {
                $contentJson = trim($data['choices'][0]['message']['content']);
                $parsedContent = json_decode($contentJson, true);

                if ($parsedContent && is_array($parsedContent)) {
                    return [
                        'success' => true,
                        'content' => [
                            'title' => $parsedContent['title'] ?? 'Untitled',
                            'sections' => $parsedContent['sections'] ?? [],
                            'topic' => $topic,
                            'type' => $contentType,
                            'generatedAt' => date('Y-m-d H:i:s')
                        ],
                        'error' => null
                    ];
                }
            }

            return [
                'success' => false,
                'content' => [],
                'error' => 'Failed to parse generated content'
            ];
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | \JsonException $e) {
            return [
                'success' => false,
                'content' => [],
                'error' => 'AI request failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build system prompt based on user type and context
     */
    private function buildSystemPrompt(string $userType, ?string $context = null): string
    {
        $basePrompt = '';

        if ($userType === 'psychologist') {
            $basePrompt = "You are an expert psychology assistant. Provide detailed, structured, and professional explanations including theories, analysis, and clinical insights. Use advanced psychological vocabulary and evidence-based approaches.";
        } else {
            $basePrompt = "You are a supportive personal development and wellness assistant. Help users improve their life, mindset, and emotional well-being using simple, clear, and encouraging language. Avoid complex clinical terminology. Be empathetic and supportive.";
        }

        if ($context) {
            $basePrompt .= " Focus your responses on the following context: {$context}.";
        }

        $basePrompt .= " Always prioritize psychological safety and suggest professional help when appropriate.";

        return $basePrompt;
    }
}
