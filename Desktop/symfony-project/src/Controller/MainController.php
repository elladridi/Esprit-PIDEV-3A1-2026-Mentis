<?php

namespace App\Controller;

use App\Repository\AssessmentRepository;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
}