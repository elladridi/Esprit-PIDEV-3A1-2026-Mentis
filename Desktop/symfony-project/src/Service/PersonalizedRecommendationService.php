<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Session;
use App\Entity\AssessmentResult;
use App\Repository\SessionRepository;
use App\Repository\AssessmentResultRepository;
use App\Repository\ContentNodeRepository;
use App\Repository\SessionReviewRepository;

class PersonalizedRecommendationService
{
    private SessionRepository $sessionRepo;
    private AssessmentResultRepository $assessmentResultRepo;
    private ContentNodeRepository $contentNodeRepo;
    private SessionReviewRepository $reviewRepo;

    public function __construct(
        SessionRepository $sessionRepo,
        AssessmentResultRepository $assessmentResultRepo,
        ContentNodeRepository $contentNodeRepo,
        SessionReviewRepository $reviewRepo
    ) {
        $this->sessionRepo = $sessionRepo;
        $this->assessmentResultRepo = $assessmentResultRepo;
        $this->contentNodeRepo = $contentNodeRepo;
        $this->reviewRepo = $reviewRepo;
    }

    public function getRecommendations(User $patient): array
    {
        $recommendations = [];
        
        // 1. Get user's reserved sessions (upcoming)
        $reservedSessions = $this->sessionRepo->findUpcomingByPatient($patient->getId());
        
        // 2. Get user's past sessions
        $pastSessions = $this->sessionRepo->findPastByPatient($patient->getId());
        
        // 3. Get user's reviews
        $userReviews = $this->reviewRepo->findBy(['patientId' => $patient->getId()]);
        
        // 4. Analyser les résultats des assessments
        $assessmentResults = $this->assessmentResultRepo->findBy(['user' => $patient]);
        $moodScore = $this->calculateAverageMoodScore($assessmentResults);
        $anxietyLevel = $this->calculateAnxietyLevel($assessmentResults);
        
        // 5. Extract user preferences from past sessions
        $preferredTypes = $this->extractPreferredSessionTypes($pastSessions, $userReviews);
        
        // 6. Recommander des sessions basées sur les préférences + mood
        $recommendations['sessions'] = $this->recommendSessions($patient, $moodScore, $anxietyLevel, $preferredTypes, $reservedSessions);
        
        // 7. Recommander du contenu éducatif
        $recommendations['content'] = $this->recommendContent($patient, $moodScore, $anxietyLevel);
        
        // 8. Conseils personnalisés
        $recommendations['tips'] = $this->generatePersonalizedTips($moodScore, $anxietyLevel);
        
        // 9. Suggestions d'activités
        $recommendations['activities'] = $this->recommendActivities($moodScore);
        
        // 10. Add user activity info for the template
        $recommendations['has_activity'] = count($pastSessions) > 0 || count($userReviews) > 0 || count($assessmentResults) > 0;
        $recommendations['reserved_count'] = count($reservedSessions);
        $recommendations['past_count'] = count($pastSessions);
        $recommendations['review_count'] = count($userReviews);
        
        return $recommendations;
    }

    private function extractPreferredSessionTypes(array $pastSessions, array $reviews): array
    {
        $preferences = [
            'types' => [],
            'high_rated_types' => [],
            'categories' => []
        ];
        
        // Count session types from past sessions
        foreach ($pastSessions as $session) {
            $type = $session->getSessionType();
            $category = $session->getCategory();
            
            if (!isset($preferences['types'][$type])) {
                $preferences['types'][$type] = 0;
            }
            $preferences['types'][$type]++;
            
            if ($category && !isset($preferences['categories'][$category])) {
                $preferences['categories'][$category] = 0;
            }
            if ($category) {
                $preferences['categories'][$category]++;
            }
        }
        
        // Analyze high-rated reviews (4-5 stars)
        foreach ($reviews as $review) {
            if ($review->getRating() >= 4) {
                $session = $this->sessionRepo->find($review->getSessionId());
                if ($session) {
                    $type = $session->getSessionType();
                    if (!isset($preferences['high_rated_types'][$type])) {
                        $preferences['high_rated_types'][$type] = 0;
                    }
                    $preferences['high_rated_types'][$type]++;
                }
            }
        }
        
        // Sort by frequency
        arsort($preferences['types']);
        arsort($preferences['high_rated_types']);
        arsort($preferences['categories']);
        
        return $preferences;
    }

    private function calculateAverageMoodScore(array $results): float
    {
        $total = 0;
        $count = 0;
        
        foreach ($results as $result) {
            $data = json_decode($result->getAnswers(), true);
            if (isset($data['mood'])) {
                $total += $data['mood'];
                $count++;
            }
        }
        
        return $count > 0 ? $total / $count : 5; // Default 5/10
    }

    private function calculateAnxietyLevel(array $results): string
    {
        $anxietyScores = [];
        
        foreach ($results as $result) {
            $data = json_decode($result->getAnswers(), true);
            if (isset($data['anxiety'])) {
                $anxietyScores[] = $data['anxiety'];
            }
        }
        
        $avg = count($anxietyScores) > 0 ? array_sum($anxietyScores) / count($anxietyScores) : 3;
        
        if ($avg <= 2) return 'low';
        if ($avg <= 4) return 'moderate';
        return 'high';
    }

    private function recommendSessions(User $patient, float $moodScore, string $anxietyLevel, array $preferences, array $reservedSessions): array
    {
        $recommendations = [];
        
        // Get reserved session IDs to exclude them
        $reservedIds = array_map(fn($s) => $s->getSessionId(), $reservedSessions);
        
        // Sessions disponibles
        $availableSessions = $this->sessionRepo->findAvailableSessions();
        
        // Filter out already reserved sessions
        $availableSessions = array_filter($availableSessions, function($session) use ($reservedIds) {
            return !in_array($session->getSessionId(), $reservedIds);
        });
        
        // If no available sessions, return empty with message
        if (empty($availableSessions)) {
            return [
                'has_sessions' => false,
                'message' => 'No available sessions at the moment. Check back soon!',
                'list' => []
            ];
        }
        
        // Filtrer et noter les sessions
        foreach ($availableSessions as $session) {
            $score = 0;
            $reasons = [];
            
            // Based on preferred session types from past sessions
            $type = $session->getSessionType();
            if (isset($preferences['types'][$type])) {
                $score += min($preferences['types'][$type] * 15, 40);
                $reasons[] = "You've enjoyed {$type} sessions before";
            }
            
            // Based on high-rated session types
            if (isset($preferences['high_rated_types'][$type])) {
                $score += min($preferences['high_rated_types'][$type] * 20, 50);
                $reasons[] = "You rated similar sessions highly";
            }
            
            // Based on category preferences
            $category = $session->getCategory();
            if ($category && isset($preferences['categories'][$category])) {
                $score += min($preferences['categories'][$category] * 10, 30);
                $reasons[] = "Matches your interest in {$category}";
            }
            
            // Based on mood
            if ($moodScore < 4 && in_array($session->getSessionType(), ['Individual', 'Online'])) {
                $score += 30;
                $reasons[] = "Recommended for your current mood";
            }
            
            // Based on anxiety level
            if ($anxietyLevel == 'high' && $category == 'Anxiety Management') {
                $score += 40;
                $reasons[] = "Helps with anxiety management";
            }
            
            // Popularity boost
            $popularityBoost = min($session->getPopularity() / 5, 20);
            $score += $popularityBoost;
            if ($popularityBoost > 10) {
                $reasons[] = "Popular among other patients";
            }
            
            // Default score if no preferences match
            if ($score == 0) {
                $score = 10;
                $reasons[] = "New session you might enjoy";
            }
            
            $recommendations[] = [
                'session' => $session,
                'score' => $score,
                'reason' => implode(' • ', array_slice($reasons, 0, 2)),
                'match_percentage' => min(round($score), 100)
            ];
        }
        
        // Trier par score et prendre les 5 meilleures
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return [
            'has_sessions' => count($recommendations) > 0,
            'message' => count($recommendations) > 0 ? 'Based on your activity, we recommend these sessions:' : 'No matching sessions found',
            'list' => array_slice($recommendations, 0, 5)
        ];
    }

    private function recommendContent(User $patient, float $moodScore, string $anxietyLevel): array
    {
        $recommendations = [];
        
        // Récupérer tout le contenu
        $allContent = $this->contentNodeRepo->findAll();
        
        if (empty($allContent)) {
            return [];
        }
        
        foreach ($allContent as $content) {
            $score = 0;
            $title = method_exists($content, 'getTitle') ? $content->getTitle() : '';
            
            // Basé sur le mood
            if ($moodScore < 4 && (str_contains($title, 'Stress') || str_contains($title, 'Anxiety'))) {
                $score += 30;
            }
            
            // Basé sur l'anxiété
            if ($anxietyLevel == 'high' && str_contains($title, 'Anxiety')) {
                $score += 40;
            }
            
            // Basé sur le type de contenu
            $type = 'Resource';
            if (method_exists($content, 'getContentType')) {
                $type = $content->getContentType();
            } elseif (method_exists($content, 'getType')) {
                $type = $content->getType();
            }
            
            if ($score > 0) {
                $recommendations[] = [
                    'content' => $content,
                    'score' => $score,
                    'type' => $type,
                    'title' => $title
                ];
            }
        }
        
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_slice($recommendations, 0, 3);
    }

    private function generatePersonalizedTips(float $moodScore, string $anxietyLevel): array
    {
        $tips = [];
        
        if ($moodScore < 3) {
            $tips[] = "💙 Your recent mood indicates you might be feeling down. Consider talking to a professional or trying our guided meditation.";
            $tips[] = "🧘 Try our 5-minute breathing exercise to help lift your mood.";
        } elseif ($moodScore < 6) {
            $tips[] = "💚 You're doing okay, but there's room for improvement. A short walk or talking to a friend might help.";
            $tips[] = "📝 Journaling your thoughts for 10 minutes can help process emotions.";
        } else {
            $tips[] = "💛 Great job maintaining a positive mood! Keep up the good work.";
            $tips[] = "🌟 Share your positive energy with others - it's contagious!";
        }
        
        if ($anxietyLevel == 'high') {
            $tips[] = "😰 We notice elevated anxiety levels. Try our anxiety management techniques.";
            $tips[] = "🎵 Listening to calming music for 15 minutes can reduce anxiety.";
        } elseif ($anxietyLevel == 'moderate') {
            $tips[] = "😌 Your anxiety is manageable. Practice deep breathing exercises.";
        }
        
        return $tips;
    }

    private function recommendActivities(float $moodScore): array
    {
        $activities = [];
        
        if ($moodScore < 4) {
            $activities = [
                ['name' => 'Guided Meditation', 'duration' => '10 min', 'benefit' => 'Reduces stress'],
                ['name' => 'Nature Walk', 'duration' => '20 min', 'benefit' => 'Improves mood'],
                ['name' => 'Journaling', 'duration' => '15 min', 'benefit' => 'Clarifies thoughts']
            ];
        } elseif ($moodScore < 7) {
            $activities = [
                ['name' => 'Breathing Exercise', 'duration' => '5 min', 'benefit' => 'Calms mind'],
                ['name' => 'Light Exercise', 'duration' => '15 min', 'benefit' => 'Boosts energy'],
                ['name' => 'Listen to Music', 'duration' => '10 min', 'benefit' => 'Elevates mood']
            ];
        } else {
            $activities = [
                ['name' => 'Share a Smile', 'duration' => '2 min', 'benefit' => 'Spreads positivity'],
                ['name' => 'Help Someone', 'duration' => '10 min', 'benefit' => 'Increases happiness'],
                ['name' => 'Learn Something New', 'duration' => '20 min', 'benefit' => 'Stimulates mind']
            ];
        }
        
        return $activities;
    }
}