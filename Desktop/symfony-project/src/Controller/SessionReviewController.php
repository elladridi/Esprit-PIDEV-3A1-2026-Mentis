<?php

namespace App\Controller;

use App\Entity\SessionReview;
use App\Entity\Session;
use App\Entity\User;
use App\Form\SessionReviewType;
use App\Repository\SessionReviewRepository;
use App\Repository\SessionRepository;
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

    // ==================== MY REVIEWS (PATIENT) ====================

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

    // ==================== ADD REVIEW ====================

    #[Route('/add/{sessionId}', name: 'review_add', methods: ['GET', 'POST'])]
    public function add(Request $request, int $sessionId): Response
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
        
        $review = new SessionReview();
        $review->setSessionId($sessionId);
        $review->setPatientId($user->getId());
        
        $form = $this->createForm(SessionReviewType::class, $review);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($review);
            $this->em->flush();
            
            // Update session average rating
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

    // ==================== EDIT REVIEW ====================

    #[Route('/{id}/edit', name: 'review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
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
            $this->em->flush();
            
            // Update session average rating
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

    // ==================== DELETE REVIEW ====================

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
            
            // Update session average rating
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

    // ==================== VIEW SESSION REVIEWS ====================

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

    // ==================== ADMIN: ALL REVIEWS ====================

    #[Route('/admin/all', name: 'review_admin_all', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminAllReviews(): Response
    {
        return $this->render('review/admin_all.html.twig', [
            'reviews' => $this->repo->findAllReviews(),
        ]);
    }
}