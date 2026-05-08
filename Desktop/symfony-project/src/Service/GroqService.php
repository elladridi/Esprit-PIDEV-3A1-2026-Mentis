<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class GroqService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private ?LoggerInterface $logger;

    private const MODEL_LARGE = 'llama-3.3-70b-versatile';
    private const MODEL_SMALL = 'llama-3.1-8b-instant';

    public function __construct(?string $groqApiKey = null, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        $this->apiKey = $groqApiKey
            ?? $_ENV['GROQ_API_KEY']
            ?? getenv('GROQ_API_KEY')
            ?: '';

        $this->apiKey = trim($this->apiKey, "'\"");

        if (empty($this->apiKey)) {
            $this->logError('GROQ_API_KEY is not set');
            throw new \RuntimeException('GROQ_API_KEY is not set');
        }
    }

    public function generateContent(string $prompt): string
    {
        $systemPrompt = "You are an expert at creating mental health assessment questions. "
            . "Generate questions in the exact format specified. "
            . "Each question must be numbered and followed by SCALE: on the next line. "
            . "NEVER create questions that ask for paragraph or long text answers. "
            . "ALL questions must be answerable with a single scale selection.";

        try {
            $decoded = $this->sendChatRequest([
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], self::MODEL_SMALL, 2000, 0.7);

            $content = $decoded['choices'][0]['message']['content'] ?? '';

            if (empty(trim($content))) {
                throw new \RuntimeException('Empty response from API');
            }

            return $this->ensureScaleLines($content);
        } catch (\Exception $e) {
            $this->logError('generateContent failed', ['error' => $e->getMessage()]);
            return $this->getFallbackQuestions($prompt);
        }
    }

    public function generateAnalysis(string $prompt): string
    {
        $this->logInfo('Generating analysis', ['prompt_length' => strlen($prompt)]);

        $cleanPrompt = strip_tags($prompt);

        $decoded = $this->sendChatRequest([
            [
                'role' => 'system',
                'content' => 'You are a compassionate clinical psychologist. '
                    . 'Analyze the assessment data and provide a detailed, empathetic response. '
                    . 'Use plain English paragraphs. NO markdown. NO bullet points. NO **bold**. '
                    . 'Write naturally like you are talking to a patient. '
                    . 'Do NOT generate questions. Only provide analysis based on the scores given.',
            ],
            [
                'role' => 'user',
                'content' => $cleanPrompt,
            ],
        ], self::MODEL_LARGE, 1500, 0.7);

        $content = $decoded['choices'][0]['message']['content'] ?? '';

        if (empty(trim($content))) {
            throw new \RuntimeException('Empty response from API');
        }

        $content = str_replace(['**', '__', '##', '# ', '`'], '', $content);

        $this->logInfo('Analysis generated', ['length' => strlen($content)]);

        return $content;
    }

    public function generateSafetyPlanSuggestions(string $prompt, string $section = 'general'): array
    {
        try {
            $decoded = $this->sendChatRequest([
                [
                    'role' => 'system',
                    'content' => $this->getSafetyPlanSystemPrompt($section),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], self::MODEL_LARGE, 800, 0.9);

            $content = $decoded['choices'][0]['message']['content'] ?? '';
            $suggestions = $this->parseSafetyPlanLines($content);

            return $suggestions ?: $this->getDynamicFallbackSuggestions($section);
        } catch (\Exception $e) {
            $this->logError('generateSafetyPlanSuggestions failed', ['error' => $e->getMessage()]);
            return $this->getDynamicFallbackSuggestions($section);
        }
    }

    public function generateFullSafetyPlan(string $context = ''): array
    {
        $contextNote = $context ? "User's situation: {$context}\n\n" : '';

        $prompt = $contextNote
            . "Create a personalized mental health crisis safety plan. "
            . "Each section should have 4-6 specific, actionable items. "
            . "Return ONLY valid JSON with this exact structure:\n"
            . "{\n"
            . "  \"warning_signs\": [],\n"
            . "  \"coping_strategies\": [],\n"
            . "  \"social_distractions\": [],\n"
            . "  \"reasons_to_live\": [],\n"
            . "  \"safe_environment\": []\n"
            . "}";

        try {
            $decoded = $this->sendChatRequest([
                [
                    'role' => 'system',
                    'content' => 'You are a clinical psychologist creating safety plans. Return ONLY valid JSON.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], self::MODEL_LARGE, 2000, 0.8);

            $content = $decoded['choices'][0]['message']['content'] ?? '{}';
            $parsed = $this->parseJsonContent($content);

            return isset($parsed['warning_signs'])
                ? $parsed
                : $this->getFallbackSafetyPlanFull();
        } catch (\Exception $e) {
            $this->logError('generateFullSafetyPlan failed', ['error' => $e->getMessage()]);
            return $this->getFallbackSafetyPlanFull();
        }
    }

    public function analyzeSentiment(string $text): array
    {
        $prompt = "Analyze this text and return ONLY valid JSON:\n"
            . "Text: \"" . str_replace('"', '\\"', $text) . "\"\n\n"
            . "{\n"
            . "  \"sentiment_label\": \"very_positive|positive|neutral|negative|very_negative|distressed\",\n"
            . "  \"sentiment_score\": 0.0,\n"
            . "  \"emotion_tags\": [],\n"
            . "  \"crisis_detected\": false,\n"
            . "  \"crisis_keywords\": [],\n"
            . "  \"key_themes\": [],\n"
            . "  \"protective_factors\": [],\n"
            . "  \"clinical_note\": \"\"\n"
            . "}";

        try {
            $decoded = $this->sendChatRequest([
                [
                    'role' => 'system',
                    'content' => 'You are a sentiment analyst. Return ONLY valid JSON.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], self::MODEL_LARGE, 600, 0.1);

            $content = $decoded['choices'][0]['message']['content'] ?? '{}';

            return $this->parseJsonContent($content) ?: $this->defaultSentiment();
        } catch (\Exception $e) {
            return $this->defaultSentiment();
        }
    }

    public function moderateReview(string $reviewText): array
    {
        $prompt = "You are a content moderator for a mental health app. "
            . "Analyze this review and determine if it contains ANY offensive, insulting, harmful, inappropriate, or disrespectful language.\n\n"
            . "Review: \"" . str_replace('"', '\\"', $reviewText) . "\"\n\n"
            . "Respond with ONLY valid JSON:\n"
            . "{\n"
            . "  \"isAppropriate\": true,\n"
            . "  \"confidence\": 0.0,\n"
            . "  \"reason\": \"brief explanation\",\n"
            . "  \"filteredVersion\": \"filtered text\",\n"
            . "  \"containsProfanity\": false,\n"
            . "  \"containsHateSpeech\": false,\n"
            . "  \"containsHarassment\": false\n"
            . "}";

        try {
            $decoded = $this->sendChatRequest([
                [
                    'role' => 'system',
                    'content' => 'You are a content moderator. Return ONLY valid JSON.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], self::MODEL_LARGE, 500, 0.1);

            $content = $decoded['choices'][0]['message']['content'] ?? '{}';

            return $this->parseJsonContent($content) ?: $this->defaultModeration($reviewText);
        } catch (\Exception $e) {
            return $this->defaultModeration($reviewText);
        }
    }

    public function generateAdaptiveQuestion(string $context, string $focus): array
    {
        $prompt = "Assessment context: {$context}\n\n"
            . "Generate ONE mental health question about {$focus}. "
            . "Use first-person. Max 20 words. Use a scale.\n\n"
            . "Return ONLY JSON: "
            . "{\"question\":\"...\",\"scale_type\":\"never_always\",\"options\":[\"Never\",\"Rarely\",\"Sometimes\",\"Often\",\"Always\"]}";

        try {
            $decoded = $this->sendChatRequest([
                [
                    'role' => 'system',
                    'content' => 'You generate assessment questions. Return ONLY valid JSON.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], self::MODEL_SMALL, 200, 0.6);

            $content = $decoded['choices'][0]['message']['content'] ?? '';

            return $this->parseJsonContent($content) ?: $this->getFallbackQuestion($focus);
        } catch (\Exception $e) {
            return $this->getFallbackQuestion($focus);
        }
    }

    private function sendChatRequest(array $messages, string $model, int $maxTokens, float $temperature): array
    {
        $payload = json_encode([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ], JSON_THROW_ON_ERROR);

        $result = $this->callApi($payload);

        if ($result === null) {
            throw new \RuntimeException('Groq API call failed');
        }

        $decoded = json_decode($result, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON response: ' . $result);
        }

        return $decoded;
    }

    private function callApi(string $jsonPayload): ?string
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 60,
        ]);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        if ($status !== 200) {
            throw new \RuntimeException('API returned HTTP ' . $status . ': ' . $result);
        }

        return $result ?: null;
    }

    private function ensureScaleLines(string $content): string
    {
        if (str_contains($content, 'SCALE:')) {
            return $content;
        }

        $lines = explode("\n", $content);
        $formatted = '';

        foreach ($lines as $line) {
            $formatted .= $line . "\n";

            if (preg_match('/^\d+\./', trim($line))) {
                $formatted .= "SCALE: Never/Rarely/Sometimes/Often/Always\n";
            }
        }

        return trim($formatted);
    }

    private function parseJsonContent(string $content): ?array
    {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $content = $matches[0];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function getSafetyPlanSystemPrompt(string $section): string
    {
        $prompts = [
            'warning_signs' => 'You are a crisis counselor. List 6 specific personal warning signs that tell someone a crisis may be coming. Return ONLY a numbered list.',
            'coping_strategies' => 'You are a crisis counselor. List 6 specific coping strategies someone can do alone to calm down during distress. Return ONLY a numbered list.',
            'social_distractions' => 'You are a crisis counselor. List 6 social activities or places that can help distract from a mental health crisis. Return ONLY a numbered list.',
            'reasons_to_live' => 'You are a crisis counselor. List 6 deeply personal reasons to continue living. Return ONLY a numbered list.',
            'safe_environment' => 'You are a crisis counselor. List 6 practical steps to make a home safer during a mental health crisis. Return ONLY a numbered list.',
            'general' => 'You are a crisis counselor. List 6 practical coping strategies for mental health. Return ONLY a numbered list.',
        ];

        return $prompts[$section] ?? $prompts['general'];
    }

    private function parseSafetyPlanLines(string $text): array
    {
        $lines = explode("\n", $text);
        $suggestions = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
            $line = preg_replace('/^[-•*]\s*/', '', $line);
            $line = trim($line);

            if (strlen($line) < 5) {
                continue;
            }

            if (stripos($line, 'SCALE:') !== false) {
                continue;
            }

            $suggestions[] = $line;
        }

        return array_slice($suggestions, 0, 7);
    }

    private function getFallbackQuestions(string $prompt): string
    {
        $lowerPrompt = strtolower($prompt);
        $topic = 'mental health';

        if (str_contains($lowerPrompt, 'anxiety')) {
            $topic = 'anxiety';
        } elseif (str_contains($lowerPrompt, 'depression')) {
            $topic = 'depression';
        } elseif (str_contains($lowerPrompt, 'stress')) {
            $topic = 'stress';
        }

        return "1. How often have I experienced {$topic} symptoms in the past two weeks?\n"
            . "SCALE: Never/Rarely/Sometimes/Often/Always\n\n"
            . "2. How much does {$topic} interfere with my daily life?\n"
            . "SCALE: Never/Rarely/Sometimes/Often/Always\n\n"
            . "3. How confident am I in managing my {$topic} symptoms?\n"
            . "SCALE: Never/Rarely/Sometimes/Often/Always";
    }

    private function getFallbackQuestion(string $focus): array
    {
        return [
            'question' => "How often have I been experiencing difficulties related to {$focus}?",
            'scale_type' => 'never_always',
            'options' => ['Never', 'Rarely', 'Sometimes', 'Often', 'Always'],
        ];
    }

    private function defaultSentiment(): array
    {
        return [
            'sentiment_label' => 'neutral',
            'sentiment_score' => 0.5,
            'emotion_tags' => [],
            'crisis_detected' => false,
            'crisis_keywords' => [],
            'key_themes' => [],
            'protective_factors' => [],
            'clinical_note' => 'Analysis temporarily unavailable. Please consult a professional.',
        ];
    }

    private function defaultModeration(string $reviewText): array
    {
        return [
            'isAppropriate' => true,
            'confidence' => 1.0,
            'reason' => 'Auto-approved',
            'filteredVersion' => $reviewText,
            'containsProfanity' => false,
            'containsHateSpeech' => false,
            'containsHarassment' => false,
        ];
    }

    private function getDynamicFallbackSuggestions(string $section): array
    {
        return [
            'Take 5 slow breaths and focus on the feeling of air entering and leaving your body.',
            'Reach out to a trusted friend, family member, or mental health professional.',
            'Move to a safer, calmer place where you are not alone.',
            'Write down what you are feeling without judging yourself.',
            'Drink water and try to do one small grounding activity.',
            'Call local emergency services if you feel at immediate risk.',
        ];
    }

    private function getFallbackSafetyPlanFull(): array
    {
        return [
            'warning_signs' => [
                'Feeling overwhelmed or unable to cope with daily tasks',
                'Withdrawing from friends, family, or social activities',
                'Significant changes in sleep patterns or appetite',
                'Loss of interest in activities I usually enjoy',
                'Increased irritability, anger, or mood swings',
            ],
            'coping_strategies' => [
                'Practice deep breathing: inhale 4 seconds, hold 4, exhale 4',
                'Go for a 15-minute walk outside',
                'Write down thoughts and feelings in a journal',
                'Listen to calming music or nature sounds',
                'Take a warm bath or shower',
            ],
            'social_distractions' => [
                'Call or text a trusted friend or family member',
                'Visit a coffee shop, library, or park',
                'Join an online support group',
                'Watch a comforting show with someone',
                'Spend time in a safe public place',
            ],
            'reasons_to_live' => [
                'People who love me and would miss me',
                'Pets or loved ones who depend on me',
                'Future goals I still want to achieve',
                'Small joys like food, music, laughter, and sunshine',
                'I have survived difficult times before',
            ],
            'safe_environment' => [
                'Ask someone trusted to stay with me',
                'Move harmful items out of reach',
                'Keep emergency contacts visible',
                'Stay in a calm and well-lit space',
                'Contact emergency services if I feel unsafe',
            ],
        ];
    }

    private function logInfo(string $message, array $context = []): void
    {
        $this->logger?->info('[GroqService] ' . $message, $context);
    }

    private function logError(string $message, array $context = []): void
    {
        $this->logger?->error('[GroqService] ' . $message, $context);
    }
}