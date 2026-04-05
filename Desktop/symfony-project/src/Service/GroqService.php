<?php

namespace App\Service;

class GroqService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        // Get API key from environment variable in constructor
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GROQ_API_KEY environment variable is not set');
        }
    }

    public function generateContent(string $prompt): string
    {
        $systemPrompt = "You are an expert at creating mental health assessment questions. "
            . "Generate questions in the exact format specified. "
            . "Each question must be numbered and followed by SCALE: on the next line.";

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

    public function generateAdaptiveQuestion(string $context, string $focus): string
    {
        $prompt = "You are an expert clinical psychologist. "
            . "Based on this assessment context: {$context}\n\n"
            . "Generate ONE specific, clinically appropriate {$focus} question. "
            . "Return ONLY the question text, no explanations, no numbering.";

        return $this->generateContent($prompt);
    }

    // ── Core cURL call ──────────────────────────────────────────────
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  // Should be true in production
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);     // Should be 2 in production
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
}