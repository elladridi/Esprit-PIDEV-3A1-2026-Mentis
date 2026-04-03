<?php

namespace App\Controller;

use App\Repository\ContentNodeRepository;
use App\Repository\ContentPathRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Redirect based on user type
        $userType = strtolower($user->getType());
        
        if ($userType === 'admin') {
            return $this->redirectToRoute('app_dashboard_admin');
        } elseif ($userType === 'psychologist') {
            return $this->redirectToRoute('app_dashboard_psychologist');
        } else {
            return $this->redirectToRoute('app_dashboard_patient');
        }
    }

    #[Route('/patient', name: 'app_dashboard_patient')]
    public function patientDashboard(ContentNodeRepository $contentNodeRepository, ContentPathRepository $contentPathRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $assignedContent = $contentNodeRepository->findAssignedToUser($user->getId());
        $progress = [];

        foreach ($assignedContent as $content) {
            $path = $contentPathRepository->findByUserContent($user, $content);
            $progress[] = [
                'content' => $content,
                'seen' => (bool)$path,
                'lastAccessed' => $path ? $path->getAccessedAt() : null,
            ];
        }

        $unread = array_filter($progress, fn($item) => !$item['seen']);

        // Advanced suggestion: top not-read content first
        usort($progress, fn($a, $b) => (int)$a['seen'] - (int)$b['seen']);

        return $this->render('dashboard/patient.html.twig', [
            'user' => $user,
            'assignedContent' => $assignedContent,
            'contentProgress' => $progress,
            'unreadCount' => count($unread),
        ]);
    }

    #[Route('/psychologist', name: 'app_dashboard_psychologist')]
    public function psychologistDashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_PSYCHOLOGIST', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('dashboard/psychologist.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin', name: 'app_dashboard_admin')]
    public function adminDashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('dashboard/admin.html.twig', [
            'user' => $user,
        ]);
    }
}
