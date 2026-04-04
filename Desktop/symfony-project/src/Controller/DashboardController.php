<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ContentNode;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\ContentNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }

        $userType = strtolower($user->getType());
        
        if ($userType === 'admin') {
            return $this->redirectToRoute('app_dashboard_admin');
        } elseif ($userType === 'psychologist') {
            return $this->redirectToRoute('app_dashboard_psychologist');
        } else {
            return $this->redirectToRoute('app_dashboard_patient');
        }
    }

    // ==================== PATIENT DASHBOARD ====================
    
    #[Route('/patient', name: 'app_dashboard_patient')]
    public function patientDashboard(ContentNodeRepository $contentNodeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        // Récupérer tous les contenus (ou ceux assignés si la relation existe)
        $assignedContent = $contentNodeRepository->findAll();

        return $this->render('dashboard/patient.html.twig', [
            'user' => $user,
            'assignedContent' => $assignedContent,
        ]);
    }

    // ==================== PSYCHOLOGIST DASHBOARD ====================
    
    #[Route('/psychologist', name: 'app_dashboard_psychologist')]
    public function psychologistDashboard(UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_PSYCHOLOGIST', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $patients = $userRepository->findBy(['type' => 'Patient']);
        $stats = $this->calculateStats($patients);
        
        return $this->render('dashboard/psychologist.html.twig', [
            'user' => $user,
            'patients' => $patients,
            'stats' => $stats,
        ]);
    }

    // ==================== ADMIN DASHBOARD ====================
    
    #[Route('/admin', name: 'app_dashboard_admin')]
    public function adminDashboard(UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $patients = $userRepository->findBy(['type' => 'Patient']);
        $psychologists = $userRepository->findBy(['type' => 'Psychologist']);
        $admins = $userRepository->findBy(['type' => 'Admin']);
        
        $patientStats = $this->calculateStats($patients);
        $psychologistStats = $this->calculateStats($psychologists);
        
        return $this->render('dashboard/admin.html.twig', [
            'user' => $user,
            'patients' => $patients,
            'psychologists' => $psychologists,
            'admins' => $admins,
            'patientStats' => $patientStats,
            'psychologistStats' => $psychologistStats,
        ]);
    }

    // ==================== AJAX ENDPOINTS FOR ADMIN ====================
    
    #[Route('/admin/patients/data', name: 'app_admin_patients_data', methods: ['GET'])]
    public function getPatientsData(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');
        $gender = $request->query->get('gender', '');
        $ageGroup = $request->query->get('ageGroup', '');
        
        $patients = $userRepository->findByFilters('Patient', $search, $sort, $order, $gender, $ageGroup);
        $stats = $this->calculateStats($patients);
        
        return $this->json([
            'patients' => array_map(function($patient) {
                return [
                    'id' => $patient->getId(),
                    'firstname' => $patient->getFirstname(),
                    'lastname' => $patient->getLastname(),
                    'email' => $patient->getEmail(),
                    'phone' => $patient->getPhone(),
                    'dateofbirth' => $patient->getDateofbirth(),
                    'age' => $patient->getAge(),
                    'gender' => $patient->getGender(),
                    'createdAt' => $patient->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $patients),
            'stats' => $stats,
            'total' => count($patients)
        ]);
    }

    #[Route('/admin/psychologists/data', name: 'app_admin_psychologists_data', methods: ['GET'])]
    public function getPsychologistsData(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');
        $gender = $request->query->get('gender', '');
        
        $psychologists = $userRepository->findByFilters('Psychologist', $search, $sort, $order, $gender);
        
        return $this->json([
            'psychologists' => array_map(function($psychologist) {
                return [
                    'id' => $psychologist->getId(),
                    'firstname' => $psychologist->getFirstname(),
                    'lastname' => $psychologist->getLastname(),
                    'email' => $psychologist->getEmail(),
                    'phone' => $psychologist->getPhone(),
                    'dateofbirth' => $psychologist->getDateofbirth(),
                    'age' => $psychologist->getAge(),
                    'gender' => $psychologist->getGender(),
                    'createdAt' => $psychologist->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $psychologists),
            'total' => count($psychologists)
        ]);
    }

    // ==================== CRUD ADMIN ====================
    
    #[Route('/admin/patient/new', name: 'app_admin_patient_new', methods: ['GET', 'POST'])]
    public function adminNewPatient(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $patient = new User();
        $patient->setType('Patient');
        
        $form = $this->createForm(UserType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($patient, $plainPassword);
                $patient->setPassword($hashedPassword);
            }
            
            $em->persist($patient);
            $em->flush();

            $this->addFlash('success', 'Patient added successfully!');
            return $this->redirectToRoute('app_dashboard_admin');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $patient,
            'title' => 'Add New Patient',
            'role' => 'patient',
        ]);
    }

    #[Route('/admin/psychologist/new', name: 'app_admin_psychologist_new', methods: ['GET', 'POST'])]
    public function adminNewPsychologist(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $psychologist = new User();
        $psychologist->setType('Psychologist');
        
        $form = $this->createForm(UserType::class, $psychologist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($psychologist, $plainPassword);
                $psychologist->setPassword($hashedPassword);
            }
            
            $em->persist($psychologist);
            $em->flush();

            $this->addFlash('success', 'Psychologist added successfully!');
            return $this->redirectToRoute('app_dashboard_admin');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $psychologist,
            'title' => 'Add New Psychologist',
            'role' => 'psychologist',
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function adminEditUser(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $em->flush();
            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_dashboard_admin');
        }

        $title = 'Edit ' . $user->getType() . ': ' . $user->getFirstname() . ' ' . $user->getLastname();
        
        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'title' => $title,
            'role' => strtolower($user->getType()),
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function adminDeleteUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('app_dashboard_admin');
    }

    // ==================== STATISTICS HELPER ====================
    
    private function calculateStats(array $users): array
    {
        $total = count($users);
        $ages = [];
        $genderCount = ['male' => 0, 'female' => 0, 'other' => 0];
        $ageGroups = [
            '0-18' => 0,
            '19-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '60+' => 0
        ];
        
        foreach ($users as $user) {
            $age = $user->getAge();
            if ($age !== null) {
                $ages[] = $age;
                if ($age <= 18) $ageGroups['0-18']++;
                elseif ($age <= 30) $ageGroups['19-30']++;
                elseif ($age <= 45) $ageGroups['31-45']++;
                elseif ($age <= 60) $ageGroups['46-60']++;
                else $ageGroups['60+']++;
            }
            
            $gender = $user->getGender();
            if ($gender && isset($genderCount[$gender])) {
                $genderCount[$gender]++;
            }
        }
        
        $averageAge = !empty($ages) ? round(array_sum($ages) / count($ages), 1) : 0;
        
        return [
            'total' => $total,
            'averageAge' => $averageAge,
            'ageGroups' => $ageGroups,
            'gender' => $genderCount,
            'minAge' => !empty($ages) ? min($ages) : 0,
            'maxAge' => !empty($ages) ? max($ages) : 0,
        ];
    }
}