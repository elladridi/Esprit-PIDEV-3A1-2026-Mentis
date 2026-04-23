<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class GroqService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private ?LoggerInterface $logger = null;

    // ── Model constants ───────────────────────────────────
    private const MODEL_LARGE = 'llama-3.3-70b-versatile';
    private const MODEL_SMALL = 'llama-3.1-8b-instant';

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
        $this->apiKey = trim($this->apiKey, "'\"");

        if (empty($this->apiKey)) {
            $this->logError('GROQ_API_KEY environment variable is not set');
            throw new \RuntimeException('GROQ_API_KEY environment variable is not set');
        }
    }

    public function generateContent(string $prompt): string
    {
        $systemPrompt = "You are an expert at creating mental health assessment questions. "
            . "Generate questions in the exact format specified. "
            . "Each question must be numbered and followed by SCALE: on the next line. "
            . "NEVER create questions that ask for paragraph or long text answers. "
            . "ALL questions must be answerable with a single scale selection.";

        $payload = json_encode([
            'model'       => self::MODEL_SMALL,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $prompt],
            ],
            'max_tokens'  => 2000,
            'temperature' => 0.7,
        ]);

        try {
            $result = $this->callApi($payload);
            $decoded = json_decode($result, true);
            $content = $decoded['choices'][0]['message']['content'] ?? '';

            if (!str_contains($content, 'SCALE:')) {
                $lines = explode("\n", $content);
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
        } catch (\Exception $e) {
            return $this->getFallbackQuestions($prompt);
        }
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
            'model'       => self::MODEL_LARGE,
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
            $result = $this->callApi($payload);
            $decoded = json_decode($result ?? '{}', true);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';
            return json_decode($content, true) ?? $this->defaultModeration($reviewText);
        } catch (\Exception $e) {
            return $this->defaultModeration($reviewText);
        }
    }

    public function generateAdaptiveQuestion(string $context, string $focus): array
    {
        $prompt = "Generate ONE mental health question about {$focus}. "
            . "Use first-person. Max 20 words. Use a scale.\n\n"
            . "Return ONLY JSON: {\"question\": \"...\", \"scale_type\": \"never_always\", \"options\": [\"Never\", \"Rarely\", \"Sometimes\", \"Often\", \"Always\"]}";

        $payload = json_encode([
            'model'       => self::MODEL_SMALL,
            'messages'    => [
                ['role' => 'system', 'content' => 'You generate assessment questions. Return ONLY valid JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 200,
            'temperature' => 0.6,
        ]);

        try {
            $result = $this->callApi($payload);
            $decoded = json_decode($result, true);
            $content = $decoded['choices'][0]['message']['content'] ?? '';
            $content = preg_replace('/```json|```/', '', $content);
            $data = json_decode(trim($content), true);
            
            return $data ?: $this->getFallbackQuestion($focus);
        } catch (\Exception $e) {
            return $this->getFallbackQuestion($focus);
        }
    }

    public function generateAnalysis(string $prompt): string
    {
        $this->logInfo('Generating analysis', ['prompt_length' => strlen($prompt)]);

        $cleanPrompt = strip_tags($prompt);
        
        $payload = json_encode([
            'model'       => self::MODEL_LARGE,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a compassionate clinical psychologist. '
                        . 'Analyze the assessment data and provide a detailed, empathetic response. '
                        . 'Use plain English paragraphs. NO markdown. NO bullet points. NO **bold**. '
                        . 'Write naturally like you are talking to a patient. '
                        . 'Do NOT generate questions. Only provide analysis based on the scores given.',
                ],
                ['role' => 'user', 'content' => $cleanPrompt],
            ],
            'max_tokens'  => 1500,
            'temperature' => 0.7,
        ]);

        try {
            $result = $this->callApi($payload);
            $decoded = json_decode($result, true);
            $content = $decoded['choices'][0]['message']['content'] ?? '';

            if (empty(trim($content))) {
                throw new \RuntimeException('Empty response from API');
            }

            $content = str_replace(['**', '__', '##', '# ', '`'], '', $content);
            
            $this->logInfo('Analysis generated', ['length' => strlen($content)]);
            return $content;

        } catch (\Exception $e) {
            $this->logError('generateAnalysis failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateSafetyPlanSuggestions(string $prompt, string $section = 'general'): array
    {
        $this->logInfo('Generating safety plan suggestions', ['section' => $section]);

        $payload = json_encode([
            'model'       => self::MODEL_LARGE,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => $this->getSafetyPlanSystemPrompt($section),
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 800,
            'temperature' => 0.9,
        ]);

        try {
            $result = $this->callApi($payload);
            $decoded = json_decode($result, true);
            $content = $decoded['choices'][0]['message']['content'] ?? '';

            $suggestions = $this->parseSafetyPlanLines($content);
            
            if (empty($suggestions)) {
                return $this->getDynamicFallbackSuggestions($section);
            }
            
            return $suggestions;

        } catch (\Exception $e) {
            $this->logError('generateSafetyPlanSuggestions failed', ['error' => $e->getMessage()]);
            return $this->getDynamicFallbackSuggestions($section);
        }
    }

    public function generateFullSafetyPlan(string $context = ''): array
    {
        $this->logInfo('Generating full safety plan', ['context' => $context]);

        $contextNote = $context ? "User's situation: {$context}\n\n" : '';

        $prompt = $contextNote 
            . "Create a personalized mental health crisis safety plan. "
            . "Each section should have 4-6 specific, actionable items. "
            . "Make the items personal and practical, not generic.\n\n"
            . "Return ONLY valid JSON with this exact structure:\n"
            . "{\n"
            . "  \"warning_signs\": [\"specific behavior 1\", \"specific behavior 2\", ...],\n"
            . "  \"coping_strategies\": [\"specific strategy 1\", \"specific strategy 2\", ...],\n"
            . "  \"social_distractions\": [\"specific person or place 1\", ...],\n"
            . "  \"reasons_to_live\": [\"personal reason 1\", \"personal reason 2\", ...],\n"
            . "  \"safe_environment\": [\"safety step 1\", \"safety step 2\", ...]\n"
            . "}\n\n"
            . "IMPORTANT: Return ONLY the JSON. No other text. No markdown.";

        $payload = json_encode([
            'model'       => self::MODEL_LARGE,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a clinical psychologist creating safety plans. '
                        . 'Return ONLY valid JSON. No markdown, no explanations, no extra text.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 2000,
            'temperature' => 0.8,
        ]);

        try {
            $result = $this->callApi($payload);
            $decoded = json_decode($result, true);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';

            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);
            
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $content = $matches[0];
            }

            $parsed = json_decode($content, true);

            if (!$parsed || !isset($parsed['warning_signs'])) {
                $this->logError('Invalid JSON response', ['content' => $content]);
                return $this->getFallbackSafetyPlanFull();
            }

            return $parsed;

        } catch (\Exception $e) {
            $this->logError('generateFullSafetyPlan failed', ['error' => $e->getMessage()]);
            return $this->getFallbackSafetyPlanFull();
        }
    }

    public function analyzeSentiment(string $text): array
    {
        $prompt = "Analyze this text and return ONLY valid JSON:\n"
            . "Text: \"" . str_replace('"', '\\"', $text) . "\"\n\n"
            . "Return JSON with these exact fields:\n"
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

        $payload = json_encode([
            'model'       => self::MODEL_LARGE,
            'messages'    => [
                ['role' => 'system', 'content' => 'You are a sentiment analyst. Return ONLY valid JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens'  => 600,
            'temperature' => 0.1,
        ]);

        try {
            $result = $this->callApi($payload);
            $decoded = json_decode($result, true);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';
            $content = preg_replace('/```json|```/', '', $content);
            $parsed = json_decode(trim($content), true);
            
            return $parsed ?: $this->defaultSentiment();
        } catch (\Exception $e) {
            return $this->defaultSentiment();
        }
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

    // ═══════════════════════════════════════════════════════
    //  PRIVATE HELPER METHODS
    // ═══════════════════════════════════════════════════════

    private function getSafetyPlanSystemPrompt(string $section): string
    {
        $prompts = [
            'warning_signs' => 'You are a crisis counselor. List 6 specific personal warning signs that tell someone a crisis may be coming. These should be internal feelings or behavior changes. Return ONLY a numbered list, one per line. No extra text.',
            'coping_strategies' => 'You are a crisis counselor. List 6 specific coping strategies someone can do alone to calm down during distress. Include breathing, movement, creative, and sensory options. Return ONLY a numbered list, one per line.',
            'social_distractions' => 'You are a crisis counselor. List 6 social activities or places that can help distract from a mental health crisis. Include calling people, going to places, and activities with others. Return ONLY a numbered list.',
            'reasons_to_live' => 'You are a crisis counselor. List 6 deeply personal reasons to continue living. Include relationships, pets, future goals, passions, and life experiences. Return ONLY a numbered list.',
            'safe_environment' => 'You are a crisis counselor. List 6 practical steps to make a home safer during a mental health crisis. Include asking for help, removing dangerous items, creating calm spaces. Return ONLY a numbered list.',
            'general' => 'You are a crisis counselor. List 6 practical coping strategies for mental health. Return ONLY a numbered list, one per line.'
        ];
        
        return $prompts[$section] ?? $prompts['general'];
    }

    private function getDynamicFallbackSuggestions(string $section): array
    {
        $fallbacks = [
            'warning_signs' => [
                'I notice changes in my sleep patterns (sleeping too much or too little)',
                'I lose interest in activities I normally enjoy',
                'I feel hopeless or trapped in my current situation',
                'I withdraw from friends, family, and social activities',
                'I have increased irritability or anger over small things',
                'I struggle to concentrate or make decisions'
            ],
            'coping_strategies' => [
                'Practice box breathing: inhale 4 sec → hold 4 sec → exhale 4 sec → hold 4 sec',
                'Go for a 15-minute walk outside, focusing on your surroundings',
                'Write down your thoughts in a journal without judgment',
                'Listen to calming music or nature sounds for 10 minutes',
                'Take a warm bath or shower with soothing scents',
                'Do gentle stretching or yoga poses for 10 minutes'
            ],
            'social_distractions' => [
                'Call or text a trusted friend - just to chat, not about problems',
                'Visit a local coffee shop or library to be around people quietly',
                'Join an online support group or community forum',
                'Go to a park or public place where others are present',
                'Attend a free community event or workshop',
                'Volunteer at an animal shelter or food bank'
            ],
            'reasons_to_live' => [
                'The people who love me and would miss me terribly',
                'My pets who depend on me for food, love, and care',
                'Future experiences I still want to have - travel, concerts, adventures',
                'Small daily joys: sunshine, good food, a good book, laughter',
                'I have survived difficult times before and grown stronger',
                'Goals I haven\'t achieved yet - career, education, personal growth'
            ],
            'safe_environment' => [
                'Ask a trusted person to hold onto any medications or dangerous items',
                'Remove sharp objects or firearms from easy reach',
                'Create a calm corner in your home with pillows, blankets, and soft lighting',
                'Stay with family or friends when feeling unsafe or alone',
                'Keep emergency contact numbers saved and visible in your phone',
                'Install safety apps or quick-dial features on your phone'
            ],
            'general' => [
                'Take 5 deep breaths, counting slowly to 5 on each inhale and exhale',
                'Reach out to a trusted friend or family member by phone or text',
                'Go for a short walk outside, noticing 5 things you can see',
                'Write down three things you are grateful for right now',
                'Listen to your favorite calming song or playlist',
                'Drink a glass of cold water slowly, focusing on the sensation'
            ]
        ];
        
        return $fallbacks[$section] ?? $fallbacks['general'];
    }

    private function getFallbackQuestions(string $prompt): string
    {
        $topic = 'mental health';
        if (strpos($prompt, 'anxiety') !== false) $topic = 'anxiety';
        if (strpos($prompt, 'depression') !== false) $topic = 'depression';
        if (strpos($prompt, 'stress') !== false) $topic = 'stress';

        return "1. How often have I experienced {$topic} symptoms in the past two weeks?\n"
             . "SCALE: Never/Rarely/Sometimes/Often/Always\n\n"
             . "2. How much does {$topic} interfere with my daily life?\n"
             . "SCALE: Never/Rarely/Sometimes/Often/Always\n\n"
             . "3. How confident am I in managing my {$topic} symptoms?\n"
             . "SCALE: Never/Rarely/Sometimes/Often/Always";
    }

    private function getFallbackSafetyPlanFull(): array
    {
        return [
            'warning_signs' => [
                'Feeling overwhelmed or unable to cope with daily tasks',
                'Withdrawing from friends, family, or social activities',
                'Significant changes in sleep patterns or appetite',
                'Loss of interest in activities I usually enjoy',
                'Increased irritability, anger, or mood swings'
            ],
            'coping_strategies' => [
                'Practice deep breathing: inhale 4 seconds, hold 4, exhale 4',
                'Go for a 15-20 minute walk outside, focusing on nature',
                'Write down my thoughts and feelings in a journal',
                'Listen to calming music or nature sounds',
                'Take a warm bath or shower to relax my body',
                'Do gentle stretching or yoga for 10 minutes'
            ],
            'social_distractions' => [
                'Call or text a trusted friend or family member',
                'Visit a local coffee shop, library, or park',
                'Join an online support group or community',
                'Go to a movie theater or watch a comforting show',
                'Volunteer at an animal shelter or community center'
            ],
            'reasons_to_live' => [
                'My family and close friends who care about me deeply',
                'My pets who depend on me for love and care',
                'Future goals and dreams I still want to achieve',
                'The small joys in life: sunsets, good food, laughter',
                'I have survived difficult times before and can do it again'
            ],
            'safe_environment' => [
                'Ask a trusted person to hold onto any medications',
                'Remove any sharp objects or potential dangers from easy reach',
                'Create a calm, comfortable space with soft lighting and blankets',
                'Stay with family or friends when feeling unsafe',
                'Keep emergency contact numbers saved and accessible',
                'Have a safety plan card in my wallet at all times'
            ],
        ];
    }

    private function getFallbackQuestion(string $focus): array
    {
        return [
            'question'   => "How often have I been experiencing difficulties related to {$focus}?",
            'scale_type' => 'never_always',
            'options'    => ['Never', 'Rarely', 'Sometimes', 'Often', 'Always'],
        ];
    }

    private function getFallbackQuestionsForReview(string $sessionType, string $sessionTitle): array
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

    private function parseSafetyPlanLines(string $text): array
    {
        $lines = explode("\n", $text);
        $suggestions = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
            $line = preg_replace('/^[-•*]\s*/', '', $line);
            $line = trim($line);
            
            if (strlen($line) < 5) continue;
            if (stripos($line, 'SCALE:') !== false) continue;
            if (preg_match('/^(Never|Rarely|Sometimes|Often|Always|Strongly)/i', $line)) continue;
            
            $suggestions[] = $line;
        }

        return array_slice($suggestions, 0, 7);
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
            'clinical_note'      => 'Analysis temporarily unavailable. Please consult a professional.',
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

    private function logInfo(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info('[GroqService] ' . $message, $context);
        }
    }

    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error('[GroqService] ' . $message, $context);
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

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

        return $result;
    }
}