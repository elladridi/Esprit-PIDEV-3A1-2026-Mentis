<?php

namespace App\Service;

class GroqService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('GROQ_API_KEY environment variable is not set');
        }
    }

    // ── Generate content (questions, analysis, etc.) ─────
    public function generateContent(string $prompt): string
    {
        $systemPrompt = "You are an expert at creating mental health assessment questions. "
            . "Generate questions in the exact format specified. "
            . "Each question must be numbered and followed by SCALE: on the next line. "
            . "NEVER create questions that ask for paragraph or long text answers. "
            . "ALL questions must be answerable with a single scale selection.";

        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $prompt],
            ],
            'max_tokens'  => 2000,
            'temperature' => 0.7,
        ]);

        $result = $this->callApi($payload);

        if ($result === null) {
            throw new \RuntimeException('Groq API call failed');
        }

        $decoded = json_decode($result, true);

        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Unexpected response: ' . $result);
        }

        $content = $decoded['choices'][0]['message']['content'];

        // Add default SCALE lines if missing
        if (!str_contains($content, 'SCALE:')) {
            $lines     = explode("\n", $content);
            $formatted = '';
            foreach ($lines as $line) {
                if (preg_match('/^\d+\./', trim($line))) {
                    $formatted .= $line . "\n";
                    $formatted .= "SCALE: Never/Rarely/Sometimes/Often/Always\n";
                } else {
                    $formatted .= $line . "\n";
                }
            }
            $content = $formatted;
        }

        return $content;
    }

    // ── Safety Plan Suggestions (NO scale injection) ─────
    // This is separate from generateContent() to avoid
    // the SCALE: lines being added automatically
    public function generateSafetyPlanSuggestions(string $prompt): array
    {
        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a compassionate clinical psychologist helping someone '
                        . 'build a personal crisis safety plan. '
                        . 'Provide practical, specific, and empathetic suggestions. '
                        . 'Return ONLY a plain numbered list. '
                        . 'Each item on its own line. '
                        . 'No introductory text. No scale labels. No extra commentary. '
                        . 'Just the numbered list items.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 600,
            'temperature' => 0.7,
        ]);

        try {
            $result  = $this->callApi($payload);
            $decoded = json_decode($result ?? '{}', true);
            $content = $decoded['choices'][0]['message']['content'] ?? '';

            return $this->parseSafetyPlanLines($content);

        } catch (\Exception $e) {
            return [];
        }
    }

    // ── Generate Full AI Safety Plan ─────────────────────
    // Returns a complete plan with all sections pre-filled
    public function generateFullSafetyPlan(string $context = ''): array
    {
        $contextNote = $context
            ? "User context: {$context}\n\n"
            : '';

        $prompt = "{$contextNote}Generate a complete personal crisis safety plan with these 5 sections. "
            . "For each section provide 4-6 specific, practical, and empathetic items. "
            . "Return ONLY valid JSON in this exact structure, no other text:\n"
            . "{\n"
            . "  \"warning_signs\": [\"item1\", \"item2\", \"item3\", \"item4\"],\n"
            . "  \"coping_strategies\": [\"item1\", \"item2\", \"item3\", \"item4\", \"item5\"],\n"
            . "  \"social_distractions\": [\"item1\", \"item2\", \"item3\", \"item4\"],\n"
            . "  \"reasons_to_live\": [\"item1\", \"item2\", \"item3\", \"item4\"],\n"
            . "  \"safe_environment\": [\"item1\", \"item2\", \"item3\", \"item4\"]\n"
            . "}\n\n"
            . "Guidelines per section:\n"
            . "- warning_signs: Internal feelings or behavioral changes that signal a crisis is coming\n"
            . "- coping_strategies: Specific solo activities to calm down (breathing, walking, journaling etc)\n"
            . "- social_distractions: People to call or places to go for distraction and support\n"
            . "- reasons_to_live: Deeply personal motivations — family, pets, goals, dreams\n"
            . "- safe_environment: Practical steps to make surroundings safer during a crisis\n"
            . "Return ONLY the JSON object, nothing else.";

        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a compassionate clinical psychologist. '
                        . 'Return ONLY valid JSON. No markdown. No extra text.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 1500,
            'temperature' => 0.8,
        ]);

        try {
            $result  = $this->callApi($payload);
            $decoded = json_decode($result ?? '{}', true);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';

            // Strip markdown fences
            $content = preg_replace('/```json|```/', '', $content);
            $content = trim($content);

            $parsed = json_decode($content, true);

            if (!$parsed || !isset($parsed['warning_signs'])) {
                return [];
            }

            return $parsed;

        } catch (\Exception $e) {
            return [];
        }
    }

    // ── Sentiment Analysis ───────────────────────────────
    public function analyzeSentiment(string $text): array
    {
        $prompt = "You are an expert clinical psychologist and sentiment analyst. "
            . "Analyze the following personal statement from a mental health assessment.\n\n"
            . "Text: \"" . str_replace('"', '\\"', $text) . "\"\n\n"
            . "Return ONLY a valid JSON object with exactly these fields:\n"
            . "{\n"
            . "  \"sentiment_label\": \"one of: very_positive, positive, neutral, negative, very_negative, distressed\",\n"
            . "  \"sentiment_score\": 0.0,\n"
            . "  \"emotion_tags\": [\"up to 5 emotions detected\"],\n"
            . "  \"crisis_detected\": false,\n"
            . "  \"crisis_keywords\": [\"any crisis-related words found\"],\n"
            . "  \"key_themes\": [\"up to 3 main themes\"],\n"
            . "  \"protective_factors\": [\"positive elements found\"],\n"
            . "  \"clinical_note\": \"one sentence clinical observation\"\n"
            . "}\n\n"
            . "sentiment_score must be between 0.0 (most negative) and 1.0 (most positive).\n"
            . "crisis_detected must be true if text contains suicidal ideation, self-harm, or hopelessness.\n"
            . "Return ONLY the JSON object, no other text.";

        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a clinical sentiment analyzer. Return ONLY valid JSON, nothing else.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 600,
            'temperature' => 0.1,
        ]);

        try {
            $result  = $this->callApi($payload);
            $decoded = json_decode($result ?? '{}', true);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';

            $content = preg_replace('/```json|```/', '', $content);
            $content = trim($content);

            $parsed = json_decode($content, true);

            if (!$parsed) {
                return $this->defaultSentiment();
            }

            return $parsed;

        } catch (\Exception $e) {
            return $this->defaultSentiment();
        }
    }

    // ── Content Moderation ───────────────────────────────
    public function moderateReview(string $reviewText): array
    {
        $prompt = "You are a content moderator for a mental health app. "
            . "Analyze this review and determine if it contains ANY offensive, insulting, "
            . "harmful, inappropriate, or disrespectful language.\n\n"
            . "Review: \"" . str_replace('"', '\\"', $reviewText) . "\"\n\n"
            . "Respond with a JSON object containing these EXACT fields (no other text):\n"
            . "{\n"
            . "  \"isAppropriate\": true,\n"
            . "  \"confidence\": 0.0,\n"
            . "  \"reason\": \"brief explanation\",\n"
            . "  \"filteredVersion\": \"filtered text\",\n"
            . "  \"containsProfanity\": false,\n"
            . "  \"containsHateSpeech\": false,\n"
            . "  \"containsHarassment\": false\n"
            . "}";

        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a content moderator. Return ONLY valid JSON.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 500,
            'temperature' => 0.1,
        ]);

        try {
            $result  = $this->callApi($payload);
            $decoded = json_decode($result ?? '{}', true);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';
            return json_decode($content, true) ?? $this->defaultModeration($reviewText);
        } catch (\Exception $e) {
            return $this->defaultModeration($reviewText);
        }
    }

    // ── Generate Adaptive Question ───────────────────────
    public function generateAdaptiveQuestion(string $context, string $focus): array
    {
        $prompt = "You are an expert clinical psychologist. {$context}

Generate ONE specific {$focus} question for a mental health assessment.

CRITICAL REQUIREMENTS:
- Question MUST use a SCALE (1-5, Never-Always, or Agree-Disagree)
- Question MUST be answerable with a single selection (NOT a paragraph)
- DO NOT ask for explanations, descriptions, or long answers
- Use first-person \"I\" statements
- Keep question short and clear (max 20 words)

Return ONLY valid JSON format:
{
    \"question\": \"I feel...\",
    \"scale_type\": \"never_always\",
    \"options\": [\"Never\", \"Rarely\", \"Sometimes\", \"Often\", \"Always\"]
}

Valid scale types:
- never_always: [\"Never\", \"Rarely\", \"Sometimes\", \"Often\", \"Always\"]
- numeric_5: [\"1\", \"2\", \"3\", \"4\", \"5\"]
- agreement: [\"Strongly Disagree\", \"Disagree\", \"Neutral\", \"Agree\", \"Strongly Agree\"]";

        $response = $this->generateContent($prompt);

        $response = trim($response);
        $response = preg_replace('/```json|```/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (!$data || !isset($data['question'])) {
            return [
                'question'   => "How often have I been experiencing issues related to {$focus}?",
                'scale_type' => 'never_always',
                'options'    => ['Never', 'Rarely', 'Sometimes', 'Often', 'Always'],
            ];
        }

        return $data;
    }

    // ── Private Helpers ──────────────────────────────────
    private function parseSafetyPlanLines(string $text): array
    {
        $lines       = explode("\n", $text);
        $suggestions = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip SCALE lines entirely
            if (stripos($line, 'SCALE:') !== false) continue;

            // Remove numbering
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);

            // Remove bullet characters
            $line = trim($line, '- •*→');
            $line = trim($line);

            // Skip short or empty lines
            if (empty($line) || strlen($line) < 5) continue;

            // Skip lines that look like scale options
            if (preg_match('/^(Never|Rarely|Sometimes|Often|Always|Strongly)/i', $line)) continue;

            $suggestions[] = $line;
        }

        return array_slice(array_values($suggestions), 0, 6);
    }

    private function defaultSentiment(): array
    {
        return [
            'sentiment_label'    => 'neutral',
            'sentiment_score'    => 0.5,
            'emotion_tags'       => [],
            'crisis_detected'    => false,
            'crisis_keywords'    => [],
            'key_themes'         => [],
            'protective_factors' => [],
            'clinical_note'      => 'Analysis unavailable.',
        ];
    }

    private function defaultModeration(string $reviewText): array
    {
        return [
            'isAppropriate'      => true,
            'confidence'         => 1.0,
            'reason'             => 'API unavailable, auto-approved',
            'filteredVersion'    => $reviewText,
            'containsProfanity'  => false,
            'containsHateSpeech' => false,
            'containsHarassment' => false,
        ];
    }

    private function callApi(string $jsonPayload): ?string
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        if ($status !== 200) {
            throw new \RuntimeException('API returned HTTP ' . $status . ': ' . $result);
        }

        return $result ?: null;
    }
}