<?php

namespace App\Service;

use App\Entity\SessionReview;
use App\Entity\Session;

class ReviewAnalysisService
{
    private GroqService $groqService;

    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }

    public function analyzeReview(SessionReview $review, Session $session): array
    {
        $comment = $review->getComment() ?? 'No comment provided';
        $rating = $review->getRating() ?? 3;
        $sessionTitle = $session->getTitle();
        
        return $this->groqService->analyzeReviewFeedback($comment, $rating, $sessionTitle);
    }
}