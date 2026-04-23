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

    /**
     * Generate personalized review questions for a session
     */
    public function generateReviewQuestions(string $sessionTitle, string $sessionType, string $category): array
    {
        $prompt = 'Generate 4 personalized review questions for a patient who just completed a therapy session.

Session Title: "' . $sessionTitle . '"
Session Type: "' . $sessionType . '"
Category: "' . $category . '"

Return ONLY valid JSON format. Use this exact structure:
{
    "questions": [
        {"id": 1, "type": "rating", "text": "How helpful was the specific technique used in this session?", "scale": 5},
        {"id": 2, "type": "choice", "text": "Would you recommend this session to a friend?", "options": ["Yes", "Maybe", "No"]},
        {"id": 3, "type": "text", "text": "What was the most valuable thing you learned today?"},
        {"id": 4, "type": "scale", "text": "How likely are you to practice what you learned?", "scale": 5}
    ]
}

Make questions specific to ' . $sessionType . ' therapy sessions about ' . $category . '. Be warm and empathetic.';

        try {
            $response = $this->generateContent($prompt);
            
            // Clean the response to extract JSON
            $response = preg_replace('/```json\s*|\s*```/', '', $response);
            $response = preg_replace('/^[^{]*/', '', $response);
            $response = preg_replace('/[^}]*$/', '', $response);
            
            $data = json_decode($response, true);
            
            if (isset($data['questions']) && count($data['questions']) >= 4) {
                return $data['questions'];
            }
            
            return $this->getFallbackQuestions($sessionType, $sessionTitle);
            
        } catch (\Exception $e) {
            return $this->getFallbackQuestions($sessionType, $sessionTitle);
        }
    }

    /**
     * Fallback questions if API fails
     */
    private function getFallbackQuestions(string $sessionType, string $sessionTitle): array
    {
        $fallbacks = [
            'Individual' => [
                ['id' => 1, 'type' => 'rating', 'text' => "How helpful was '{$sessionTitle}' for you?", 'scale' => 5],
                ['id' => 2, 'type' => 'choice', 'text' => 'Would you recommend this session to a friend?', 'options' => ['Yes', 'Maybe', 'No']],
                ['id' => 3, 'type' => 'text', 'text' => 'What was the most valuable thing you learned today?'],
                ['id' => 4, 'type' => 'scale', 'text' => 'How likely are you to apply what you learned?', 'scale' => 5]
            ],
            'Group' => [
                ['id' => 1, 'type' => 'rating', 'text' => 'How comfortable did you feel sharing in the group?', 'scale' => 5],
                ['id' => 2, 'type' => 'choice', 'text' => 'Would you join another group session?', 'options' => ['Yes', 'Maybe', 'No']],
                ['id' => 3, 'type' => 'text', 'text' => 'What did you appreciate most about the group dynamic?'],
                ['id' => 4, 'type' => 'scale', 'text' => 'How likely are you to recommend this group to others?', 'scale' => 5]
            ],
            'Online' => [
                ['id' => 1, 'type' => 'rating', 'text' => 'How was your experience with the online platform?', 'scale' => 5],
                ['id' => 2, 'type' => 'choice', 'text' => 'Would you prefer more online sessions?', 'options' => ['Yes', 'Maybe', 'No']],
                ['id' => 3, 'type' => 'text', 'text' => 'What could improve the online session experience?'],
                ['id' => 4, 'type' => 'scale', 'text' => 'How likely are you to continue with online sessions?', 'scale' => 5]
            ],
            'Family' => [
                ['id' => 1, 'type' => 'rating', 'text' => 'How helpful was the session for your family?', 'scale' => 5],
                ['id' => 2, 'type' => 'choice', 'text' => 'Would you recommend family therapy to others?', 'options' => ['Yes', 'Maybe', 'No']],
                ['id' => 3, 'type' => 'text', 'text' => 'What communication tool was most useful?'],
                ['id' => 4, 'type' => 'scale', 'text' => 'How likely are you to practice the family exercises?', 'scale' => 5]
            ]
        ];

        return $fallbacks[$sessionType] ?? $fallbacks['Individual'];
    }

    /**
     * Analyze a review and provide feedback for psychologists
     */
    public function analyzeReviewFeedback(string $reviewText, int $rating, string $sessionTitle): array
    {
        $sentiment = $rating >= 4 ? 'positive' : ($rating <= 2 ? 'negative' : 'neutral');
        
        $prompt = "You are an expert clinical supervisor analyzing a patient's session review. 
        
Review: \"{$reviewText}\"
Rating: {$rating}/5 stars
Session Title: \"{$sessionTitle}\"

Based on this review, provide a professional analysis for the psychologist.

Return ONLY valid JSON format. Use this exact structure:
{
    \"sentiment\": \"{$sentiment}\",
    \"analysis\": {
        \"key_points\": [\"point 1\", \"point 2\", \"point 3\"],
        \"patient_sentiment\": \"What the patient is feeling\",
        \"what_went_well\": [\"aspect 1\", \"aspect 2\"],
        \"areas_for_improvement\": [\"area 1\", \"area 2\"]
    },
    \"advice\": \"Professional advice for the psychologist based on this review\",
    \"action_items\": [\"action 1\", \"action 2\", \"action 3\"],
    \"encouragement\": \"Encouraging message for the psychologist\"
}

For {$sentiment} reviews:
- If positive: Focus on what worked well, encourage maintaining those practices
- If negative: Provide constructive advice, suggest specific improvements
- If neutral: Offer balanced feedback and suggestions for enhancement";

        try {
            $response = $this->generateContent($prompt);
            
            // Clean the response to extract JSON
            $response = preg_replace('/```json\s*|\s*```/', '', $response);
            $response = preg_replace('/^[^{]*/', '', $response);
            $response = preg_replace('/[^}]*$/', '', $response);
            
            $data = json_decode($response, true);
            
            if (isset($data['analysis'])) {
                return $data;
            }
            
            return $this->getDefaultAnalysis($rating, $sessionTitle);
            
        } catch (\Exception $e) {
            return $this->getDefaultAnalysis($rating, $sessionTitle);
        }
    }

    /**
     * Default analysis when API fails
     */
    private function getDefaultAnalysis(int $rating, string $sessionTitle): array
    {
        if ($rating >= 4) {
            return [
                'sentiment' => 'positive',
                'analysis' => [
                    'key_points' => ['Patient expressed satisfaction', 'Session was well received'],
                    'patient_sentiment' => 'Satisfied and positive',
                    'what_went_well' => ['Session structure', 'Therapeutic approach'],
                    'areas_for_improvement' => ['Continue current practices']
                ],
                'advice' => "Great job! Keep maintaining the quality of your '{$sessionTitle}' sessions.",
                'action_items' => ['Continue current approach', 'Ask for specific feedback next time'],
                'encouragement' => "Excellent work! Your patients appreciate your sessions. Keep it up!"
            ];
        } elseif ($rating <= 2) {
            return [
                'sentiment' => 'negative',
                'analysis' => [
                    'key_points' => ['Patient expressed concerns', 'Room for improvement identified'],
                    'patient_sentiment' => 'Dissatisfied or frustrated',
                    'what_went_well' => ['Patient completed the session'],
                    'areas_for_improvement' => ['Communication', 'Session pacing', 'Patient engagement']
                ],
                'advice' => "Consider reviewing your approach for '{$sessionTitle}'. Focus on patient engagement and communication.",
                'action_items' => ['Follow up with patient', 'Review session structure', 'Seek peer consultation'],
                'encouragement' => "Every review is a learning opportunity. Use this feedback to grow professionally."
            ];
        } else {
            return [
                'sentiment' => 'neutral',
                'analysis' => [
                    'key_points' => ['Patient was neither very satisfied nor dissatisfied'],
                    'patient_sentiment' => 'Neutral or indifferent',
                    'what_went_well' => ['Session was completed'],
                    'areas_for_improvement' => ['Engagement', 'Personalization', 'Follow-up']
                ],
                'advice' => "The patient had a neutral experience. Consider asking what would make their next session better.",
                'action_items' => ['Reach out to patient', 'Ask for specific preferences', 'Enhance session personalization'],
                'encouragement' => "Continue refining your approach. Every session is progress!"
            ];
        }
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