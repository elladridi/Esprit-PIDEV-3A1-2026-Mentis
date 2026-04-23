<?php

namespace App\Controller;

use App\Repository\AssessmentRepository;
use App\Repository\SessionRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(AssessmentRepository $assessmentRepo, SessionRepository $sessionRepo): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        // If user is logged in, redirect to their specific dashboard
        if ($user) {
            $userType = strtolower($user->getType());
            
            if ($userType === 'admin') {
                return $this->redirectToRoute('app_dashboard_admin');
            } elseif ($userType === 'psychologist') {
                return $this->redirectToRoute('app_dashboard_psychologist');
            } elseif ($userType === 'patient') {
                return $this->redirectToRoute('app_dashboard_patient');
            }
        }
        
        // For non-logged in users, show the public home page
        $activeAssessments = $assessmentRepo->findAllActive();
        $availableSessions = $sessionRepo->findAvailableSessions();
        
        return $this->render('main/home.html.twig', [
            'assessments' => $activeAssessments,
            'availableSessions' => $availableSessions,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('main/about.html.twig');
    }

    #[Route('/services', name: 'app_services')]
    public function services(): Response
    {
        return $this->render('main/services.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('main/contact.html.twig');
    }

    #[Route('/set-locale/{locale}', name: 'set_locale', methods: ['POST'])]
    public function setLocale(string $locale, Request $request): JsonResponse
    {
        $supportedLocales = ['en', 'fr', 'es', 'ar'];
        
        if (in_array($locale, $supportedLocales)) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
            return $this->json(['success' => true]);
        }
        
        return $this->json(['success' => false, 'message' => 'Locale not supported'], 400);
    }

    #[Route('/change-lang/{locale}', name: 'change_lang')]
    public function changeLang(string $locale, Request $request): Response
    {
        $supportedLocales = ['en', 'fr', 'es', 'ar'];
        
        if (in_array($locale, $supportedLocales)) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        }
        
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('app_home');
    }
}