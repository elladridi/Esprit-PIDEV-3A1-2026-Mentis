<?php

namespace App\Controller;

use App\Repository\AssessmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(AssessmentRepository $assessmentRepo): Response
    {
        // Get active assessments for patients to display in horizontal scroll
        $assessments = $assessmentRepo->findAllActive();
        
        return $this->render('main/home.html.twig', [
            'assessments' => $assessments,
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