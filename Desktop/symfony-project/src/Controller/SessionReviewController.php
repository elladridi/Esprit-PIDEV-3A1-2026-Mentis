<?php

namespace App\Controller;

use App\Entity\SessionReview;
use App\Entity\Session;
use App\Entity\User;
use App\Form\SessionReviewType;
use App\Repository\SessionReviewRepository;
use App\Repository\SessionRepository;
use App\Service\BadWordFilterService;
use App\Service\GroqService;
use App\Service\ReviewAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/review')]
class SessionReviewController extends AbstractController
{
    private EntityManagerInterface $em;
    private SessionReviewRepository $repo;
    private SessionRepository $sessionRepo;

    public function __construct(
        EntityManagerInterface $em,
        SessionReviewRepository $repo,
        SessionRepository $sessionRepo
    ) {
        $this->em = $em;
        $this->repo = $repo;
        $this->sessionRepo = $sessionRepo;
    }

    #[Route('/my-reviews', name: 'review_my_reviews', methods: ['GET'])]
    public function myReviews(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || $user->getType() !== 'Patient') {
            $this->addFlash('error', 'Only patients can view reviews.');
            return $this->redirectToRoute('app_home');
        }
        
        return $this->render('review/my_reviews.html.twig', [
            'reviews' => $this->repo->findByPatient($user->getId()),
        ]);
    }

    #[Route('/add/{sessionId}', name: 'review_add', methods: ['GET', 'POST'])]
    public function add(Request $request, int $sessionId, BadWordFilterService $badWordFilter, GroqService $groqService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || $user->getType() !== 'Patient') {
            $this->addFlash('error', 'Only patients can add reviews.');
            return $this->redirectToRoute('app_home');
        }
        
        $session = $this->sessionRepo->find($sessionId);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        $today = new \DateTime();
        $sessionDate = $session->getSessionDate();
        
        if ($sessionDate > $today) {
            $this->addFlash('error', 'You can only review past sessions.');
            return $this->redirectToRoute('session_by_patient', ['patientId' => $user->getId()]);
        }
        
        if ($this->repo->hasPatientReviewed($sessionId, $user->getId())) {
            $this->addFlash('error', 'You have already reviewed this session.');
            return $this->redirectToRoute('session_by_patient', ['patientId' => $user->getId()]);
        }
        
        $mode = $request->get('mode', $request->request->get('mode', 'choice'));
        
        // CHOICE SCREEN
        if ($mode === 'choice') {
            return $this->render('review/choice.html.twig', [
                'session' => $session,
            ]);
        }
        
        // AI MODE
        if ($mode === 'ai') {
            // Show the form (GET request)
            if ($request->isMethod('GET')) {
                $questions = $groqService->generateReviewQuestions(
                    $session->getTitle(),
                    $session->getSessionType(),
                    $session->getCategory() ?? 'General'
                );
                
                return $this->render('review/dynamic_add.html.twig', [
                    'session' => $session,
                    'questions' => $questions
                ]);
            }
            
            // Process the form (POST request)
            if ($request->isMethod('POST')) {
                $questions = $groqService->generateReviewQuestions(
                    $session->getTitle(),
                    $session->getSessionType(),
                    $session->getCategory() ?? 'General'
                );
                
                $allData = $request->request->all();
                
                $reviewText = "📝 Review for: " . $session->getTitle() . "\n";
                $reviewText .= "📅 Date: " . date('Y-m-d H:i') . "\n";
                $reviewText .= str_repeat('─', 50) . "\n\n";
                
                $rating = 5;
                
                foreach ($questions as $question) {
                    $fieldName = 'q_' . $question['id'];
                    if (isset($allData[$fieldName]) && !empty($allData[$fieldName])) {
                        $answer = $allData[$fieldName];
                        $reviewText .= "• " . $question['text'] . "\n";
                        $reviewText .= "  → Answer: " . $answer . "\n\n";
                        
                        if ($question['type'] === 'rating' && is_numeric($answer)) {
                            $rating = min(5, max(1, (int)$answer));
                        }
                    }
                }
                
                if ($badWordFilter->containsBadWords($reviewText)) {
                    $badWords = $badWordFilter->getBadWordsFound($reviewText);
                    $this->addFlash('error', '❌ Inappropriate language: ' . implode(', ', $badWords));
                    return $this->render('review/dynamic_add.html.twig', [
                        'session' => $session,
                        'questions' => $questions
                    ]);
                }
                
                $cleanComment = $badWordFilter->filterBadWords($reviewText);
                
                $review = new SessionReview();
                $review->setSessionId($sessionId);
                $review->setPatientId($user->getId());
                $review->setRating($rating);
                $review->setComment($cleanComment);
                $review->setReviewDate(new \DateTime());
                $review->setIsAppropriate(true);
                
                $this->em->persist($review);
                $this->em->flush();
                
                $avgRating = $this->repo->getAverageRating($sessionId);
                $session->setAverageRating($avgRating);
                $this->em->flush();
                
                $this->addFlash('success', 'Thank you for your review!');
                return $this->redirectToRoute('review_my_reviews');
            }
        }
        
        // TRADITIONAL MODE
        $review = new SessionReview();
        $review->setSessionId($sessionId);
        $review->setPatientId($user->getId());
        
        $form = $this->createForm(SessionReviewType::class, $review);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $review->getComment();
            
            if ($comment && $badWordFilter->containsBadWords($comment)) {
                $badWords = $badWordFilter->getBadWordsFound($comment);
                $this->addFlash('error', '❌ Inappropriate language: ' . implode(', ', $badWords));
                return $this->render('review/add.html.twig', [
                    'form' => $form->createView(),
                    'session' => $session,
                ]);
            }
            
            if ($comment) {
                $cleanComment = $badWordFilter->filterBadWords($comment);
                $review->setComment($cleanComment);
            }
            
            $review->setIsAppropriate(true);
            
            $this->em->persist($review);
            $this->em->flush();
            
            $avgRating = $this->repo->getAverageRating($sessionId);
            $session->setAverageRating($avgRating);
            $this->em->flush();
            
            $this->addFlash('success', 'Thank you for your review!');
            return $this->redirectToRoute('review_my_reviews');
        }
        
        return $this->render('review/add.html.twig', [
            'form' => $form->createView(),
            'session' => $session,
        ]);
    }

    #[Route('/{id}/edit', name: 'review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, BadWordFilterService $badWordFilter): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || $user->getType() !== 'Patient') {
            $this->addFlash('error', 'Only patients can edit reviews.');
            return $this->redirectToRoute('app_home');
        }
        
        $review = $this->repo->find($id);
        
        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }
        
        if ($review->getPatientId() !== $user->getId()) {
            $this->addFlash('error', 'You can only edit your own reviews.');
            return $this->redirectToRoute('review_my_reviews');
        }
        
        $form = $this->createForm(SessionReviewType::class, $review);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $review->getComment();
            
            if ($comment && $badWordFilter->containsBadWords($comment)) {
                $badWords = $badWordFilter->getBadWordsFound($comment);
                $this->addFlash('error', '❌ Inappropriate language: ' . implode(', ', $badWords));
                
                $session = $this->sessionRepo->find($review->getSessionId());
                return $this->render('review/edit.html.twig', [
                    'form' => $form->createView(),
                    'review' => $review,
                    'session' => $session,
                ]);
            }
            
            if ($comment) {
                $cleanComment = $badWordFilter->filterBadWords($comment);
                $review->setComment($cleanComment);
            }
            
            $this->em->flush();
            
            $avgRating = $this->repo->getAverageRating($review->getSessionId());
            $session = $this->sessionRepo->find($review->getSessionId());
            if ($session) {
                $session->setAverageRating($avgRating);
                $this->em->flush();
            }
            
            $this->addFlash('success', 'Review updated successfully!');
            return $this->redirectToRoute('review_my_reviews');
        }
        
        $session = $this->sessionRepo->find($review->getSessionId());
        
        return $this->render('review/edit.html.twig', [
            'form' => $form->createView(),
            'review' => $review,
            'session' => $session,
        ]);
    }

    #[Route('/{id}/delete', name: 'review_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || $user->getType() !== 'Patient') {
            $this->addFlash('error', 'Only patients can delete reviews.');
            return $this->redirectToRoute('app_home');
        }
        
        $review = $this->repo->find($id);
        
        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }
        
        if ($review->getPatientId() !== $user->getId()) {
            $this->addFlash('error', 'You can only delete your own reviews.');
            return $this->redirectToRoute('review_my_reviews');
        }
        
        if ($this->isCsrfTokenValid('delete' . $review->getReviewId(), $request->request->get('_token'))) {
            $sessionId = $review->getSessionId();
            $this->em->remove($review);
            $this->em->flush();
            
            $avgRating = $this->repo->getAverageRating($sessionId);
            $session = $this->sessionRepo->find($sessionId);
            if ($session) {
                $session->setAverageRating($avgRating);
                $this->em->flush();
            }
            
            $this->addFlash('success', 'Review deleted successfully!');
        }
        
        return $this->redirectToRoute('review_my_reviews');
    }

    #[Route('/session/{sessionId}', name: 'review_session_reviews', methods: ['GET'])]
    public function sessionReviews(int $sessionId): Response
    {
        $session = $this->sessionRepo->find($sessionId);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        return $this->render('review/session_reviews.html.twig', [
            'session' => $session,
            'reviews' => $this->repo->findBySession($sessionId),
            'averageRating' => $this->repo->getAverageRating($sessionId),
            'reviewCount' => $this->repo->getReviewCount($sessionId),
        ]);
    }

    #[Route('/admin/all', name: 'review_admin_all', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminAllReviews(): Response
    {
        return $this->render('review/admin_all.html.twig', [
            'reviews' => $this->repo->findAllReviews(),
        ]);
    }

    #[Route('/admin/delete/{id}', name: 'review_admin_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDelete(int $id, Request $request): Response
    {
        $review = $this->repo->find($id);
        
        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }
        
        if ($this->isCsrfTokenValid('delete' . $review->getReviewId(), $request->request->get('_token'))) {
            $sessionId = $review->getSessionId();
            $this->em->remove($review);
            $this->em->flush();
            
            $avgRating = $this->repo->getAverageRating($sessionId);
            $session = $this->sessionRepo->find($sessionId);
            if ($session) {
                $session->setAverageRating($avgRating);
                $this->em->flush();
            }
            
            $this->addFlash('success', 'Review deleted successfully by admin!');
        }
        
        return $this->redirectToRoute('review_admin_all');
    }

    // ==================== AI REVIEW ANALYSIS ====================

    #[Route('/admin/analyze/{id}', name: 'review_admin_analyze', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function analyzeReview(int $id, ReviewAnalysisService $analysisService): Response
    {
        $review = $this->repo->find($id);
        
        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }
        
        $session = $this->sessionRepo->find($review->getSessionId());
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        $analysis = $analysisService->analyzeReview($review, $session);
        
        // Store analysis in session for display
        $this->addFlash('analysis', json_encode($analysis));
        
        return $this->redirectToRoute('review_admin_all');
    }

    #[Route('/admin/analysis/{id}', name: 'review_admin_analysis_view', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function viewAnalysis(int $id, ReviewAnalysisService $analysisService): Response
    {
        $review = $this->repo->find($id);
        
        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }
        
        $session = $this->sessionRepo->find($review->getSessionId());
        $analysis = $analysisService->analyzeReview($review, $session);
        
        return $this->render('review/analysis.html.twig', [
            'review' => $review,
            'session' => $session,
            'analysis' => $analysis
        ]);
    }
}