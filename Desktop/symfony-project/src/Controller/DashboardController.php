<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ContentNode;
use App\Entity\LoginAttempt;
use App\Entity\ContentPath;
use App\Form\UserType;
use App\Repository\LoginAttemptRepository;
use App\Repository\UserRepository;
use App\Repository\ContentNodeRepository;
use App\Repository\AssessmentResultRepository;
use App\Repository\ContentPathRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\BadgeService;

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
    public function patientDashboard(
        ContentNodeRepository $contentNodeRepository,
        AssessmentResultRepository $resultRepository,
        PaginatorInterface $paginator,
        BadgeService $badgeService,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $assignedContent = $contentNodeRepository->findAssignedToUserPhp($user->getId());

        $resultsQuery = $resultRepository->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.takenAt', 'DESC')
            ->getQuery();

        $recentResults = $paginator->paginate(
            $resultsQuery,
            $request->query->getInt('page', 1),
            5
        );
        
        // Get badges
        $badges = $badgeService->getUserBadges($user);
        $earnedBadges = array_filter($badges, fn($b) => $b['earned']);
        $totalBadges = count($badges);
        
        // Get newly earned badges from session (if any)
        $session = $request->getSession();
        $newlyEarnedBadges = $session->get('new_badges_earned', []);
        $session->remove('new_badges_earned');

        return $this->render('dashboard/patient.html.twig', [
            'user' => $user,
            'assignedContent' => $assignedContent,
            'recentResults' => $recentResults,
            'badges' => $badges,
            'earnedBadges' => $earnedBadges,
            'totalBadges' => $totalBadges,
            'newlyEarnedBadges' => $newlyEarnedBadges,
        ]);
    }

    // ==================== PSYCHOLOGIST DASHBOARD ====================
    
    #[Route('/psychologist', name: 'app_dashboard_psychologist')]
    public function psychologistDashboard(
        UserRepository $userRepository,
        AssessmentResultRepository $resultRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_PSYCHOLOGIST', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $patientsQuery = $userRepository->createQueryBuilder('u')
            ->where('u.type = :type')
            ->setParameter('type', 'Patient')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery();

        $patients = $paginator->paginate(
            $patientsQuery,
            $request->query->getInt('patientPage', 1),
            10
        );

        $resultsQuery = $resultRepository->createQueryBuilder('r')
            ->orderBy('r.takenAt', 'DESC')
            ->getQuery();

        $results = $paginator->paginate(
            $resultsQuery,
            $request->query->getInt('resultsPage', 1),
            10
        );

        $allPatients = $userRepository->findBy(['type' => 'Patient']);
        $stats = $this->calculateStats($allPatients);

        return $this->render('dashboard/psychologist.html.twig', [
            'user'     => $user,
            'patients' => $patients,
            'results'  => $results,
            'stats'    => $stats,
        ]);
    }

    // ==================== ADMIN DASHBOARD ====================
    
    #[Route('/admin', name: 'app_dashboard_admin')]
    public function adminDashboard(
        UserRepository $userRepository,
        ContentPathRepository $logRepo,
        LoginAttemptRepository $loginAttemptRepo,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $patientsQuery = $userRepository->createQueryBuilder('u')
            ->where('u.type = :type')
            ->setParameter('type', 'Patient')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery();

        $patients = $paginator->paginate(
            $patientsQuery,
            $request->query->getInt('patientPage', 1),
            10
        );

        $psychologistsQuery = $userRepository->createQueryBuilder('u')
            ->where('u.type = :type')
            ->setParameter('type', 'Psychologist')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery();

        $psychologists = $paginator->paginate(
            $psychologistsQuery,
            $request->query->getInt('psychologistPage', 1),
            10
        );

        $logsQuery = $logRepo->createQueryBuilder('l')
            ->orderBy('l.accessedAt', 'DESC')
            ->getQuery();

        $logs = $paginator->paginate(
            $logsQuery,
            $request->query->getInt('logPage', 1),
            20
        );

        $admins = $userRepository->findBy(['type' => 'Admin']);

        $allPatients      = $userRepository->findBy(['type' => 'Patient']);
        $allPsychologists = $userRepository->findBy(['type' => 'Psychologist']);
        $patientStats      = $this->calculateStats($allPatients);
        $psychologistStats = $this->calculateStats($allPsychologists);

        $totalAttempts = $loginAttemptRepo->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $failedAttempts = $loginAttemptRepo->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.wasSuccessful = false')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $securityStats = [
            'total_attempts'  => (int) $totalAttempts,
            'failed_attempts' => (int) $failedAttempts,
        ];

        $bannedUsers = $userRepository->findBy(['isBanned' => true]);

        return $this->render('dashboard/admin.html.twig', [
            'user'               => $user,
            'patients'           => $patients,
            'psychologists'      => $psychologists,
            'admins'             => $admins,
            'patientStats'       => $patientStats,
            'psychologistStats'  => $psychologistStats,
            'logs'               => $logs,
            'stats'              => $securityStats,
            'bannedUsers'        => $bannedUsers,
        ]);
    }

    // ==================== AJAX ENDPOINTS FOR ADMIN ====================
    
    #[Route('/admin/patients/data', name: 'app_admin_patients_data', methods: ['GET'])]
    public function getPatientsData(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $search   = $request->query->get('search', '');
            $gender   = $request->query->get('gender', '');
            $ageGroup = $request->query->get('ageGroup', '');
            $page     = $request->query->getInt('page', 1);
            $limit    = $request->query->getInt('limit', 10);

            $allowedSorts = ['id', 'firstname', 'lastname', 'email', 'phone', 'createdAt'];
            $sort  = in_array($request->query->get('sortField', 'id'), $allowedSorts)
                ? $request->query->get('sortField', 'id')
                : 'id';
            $order = strtoupper($request->query->get('sortOrder', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            $buildQb = function () use ($userRepository, $search, $gender, $sort, $order) {
                $qb = $userRepository->createQueryBuilder('u')
                    ->where('u.type = :type')
                    ->setParameter('type', 'Patient');

                if ($search) {
                    $qb->andWhere('u.firstname LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search OR u.phone LIKE :search')
                       ->setParameter('search', '%' . $search . '%');
                }
                if ($gender) {
                    $qb->andWhere('u.gender = :gender')->setParameter('gender', $gender);
                }

                $qb->orderBy('u.' . $sort, $order);
                return $qb;
            };

            $pagination = $paginator->paginate($buildQb()->getQuery(), $page, $limit);

            $patients = [];
            foreach ($pagination->getItems() as $patient) {
                $patients[] = [
                    'id'          => $patient->getId(),
                    'firstname'   => $patient->getFirstname(),
                    'lastname'    => $patient->getLastname(),
                    'email'       => $patient->getEmail(),
                    'phone'       => $patient->getPhone(),
                    'dateofbirth' => $patient->getDateofbirth()?->format('Y-m-d') ?? '',
                    'age'         => $patient->getAge(),
                    'gender'      => $patient->getGender(),
                    'createdAt'   => $patient->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ];
            }

            $allPatients = $buildQb()->getQuery()->getResult();

            if ($ageGroup) {
                [$min, $max] = match ($ageGroup) {
                    '0-18'  => [0, 18],
                    '19-30' => [19, 30],
                    '31-45' => [31, 45],
                    '46-60' => [46, 60],
                    '60+'   => [61, 999],
                    default => [0, 999],
                };
                $allPatients = array_filter(
                    $allPatients,
                    fn($u) => $u->getAge() !== null && $u->getAge() >= $min && $u->getAge() <= $max
                );
            }

            $stats = $this->calculateStats(array_values($allPatients));

            return $this->json([
                'patients'    => $patients,
                'stats'       => $stats,
                'total'       => $pagination->getTotalItemCount(),
                'currentPage' => $page,
                'totalPages'  => (int) ceil($pagination->getTotalItemCount() / $limit),
                'limit'       => $limit,
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/admin/psychologists/data', name: 'app_admin_psychologists_data', methods: ['GET'])]
    public function getPsychologistsData(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $search = $request->query->get('search', '');
            $gender = $request->query->get('gender', '');
            $page   = $request->query->getInt('page', 1);
            $limit  = $request->query->getInt('limit', 10);

            $allowedSorts = ['id', 'firstname', 'lastname', 'email', 'phone', 'createdAt'];
            $sort  = in_array($request->query->get('sortField', 'id'), $allowedSorts)
                ? $request->query->get('sortField', 'id')
                : 'id';
            $order = strtoupper($request->query->get('sortOrder', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            $qb = $userRepository->createQueryBuilder('u')
                ->where('u.type = :type')
                ->setParameter('type', 'Psychologist');

            if ($search) {
                $qb->andWhere('u.firstname LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search OR u.phone LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }
            if ($gender) {
                $qb->andWhere('u.gender = :gender')->setParameter('gender', $gender);
            }

            $qb->orderBy('u.' . $sort, $order);

            $pagination = $paginator->paginate($qb->getQuery(), $page, $limit);

            $psychologists = [];
            foreach ($pagination->getItems() as $psych) {
                $psychologists[] = [
                    'id'          => $psych->getId(),
                    'firstname'   => $psych->getFirstname(),
                    'lastname'    => $psych->getLastname(),
                    'email'       => $psych->getEmail(),
                    'phone'       => $psych->getPhone(),
                    'dateofbirth' => $psych->getDateofbirth()?->format('Y-m-d') ?? '',
                    'age'         => $psych->getAge(),
                    'gender'      => $psych->getGender(),
                    'createdAt'   => $psych->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                ];
            }

            return $this->json([
                'psychologists' => $psychologists,
                'total'         => $pagination->getTotalItemCount(),
                'currentPage'   => $page,
                'totalPages'    => (int) ceil($pagination->getTotalItemCount() / $limit),
                'limit'         => $limit,
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    // ==================== CRUD ADMIN ====================
    
    #[Route('/admin/patient/new', name: 'app_admin_patient_new', methods: ['GET', 'POST'])]
    public function adminNewPatient(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $patient = new User();
        $patient->setType('Patient');

        $form = $this->createForm(UserType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $patient->setPassword($passwordHasher->hashPassword($patient, $plainPassword));
            }
            $em->persist($patient);
            $em->flush();
            $this->addFlash('success', 'Patient added successfully!');
            return $this->redirectToRoute('app_dashboard_admin');
        }

        return $this->render('admin/user_form.html.twig', [
            'form'  => $form->createView(),
            'user'  => $patient,
            'title' => 'Add New Patient',
            'role'  => 'patient',
        ]);
    }

    #[Route('/admin/psychologist/new', name: 'app_admin_psychologist_new', methods: ['GET', 'POST'])]
    public function adminNewPsychologist(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $psychologist = new User();
        $psychologist->setType('Psychologist');

        $form = $this->createForm(UserType::class, $psychologist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $psychologist->setPassword($passwordHasher->hashPassword($psychologist, $plainPassword));
            }
            $em->persist($psychologist);
            $em->flush();
            $this->addFlash('success', 'Psychologist added successfully!');
            return $this->redirectToRoute('app_dashboard_admin');
        }

        return $this->render('admin/user_form.html.twig', [
            'form'  => $form->createView(),
            'user'  => $psychologist,
            'title' => 'Add New Psychologist',
            'role'  => 'psychologist',
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function adminEditUser(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }
            $em->flush();
            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_dashboard_admin');
        }

        return $this->render('admin/user_form.html.twig', [
            'form'  => $form->createView(),
            'user'  => $user,
            'title' => 'Edit ' . $user->getType() . ': ' . $user->getFullName(),
            'role'  => strtolower($user->getType()),
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function adminDeleteUser(
        Request $request,
        User $user,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('app_dashboard_admin');
    }

    #[Route('/admin/logs/delete/{id}', name: 'app_admin_log_delete', methods: ['POST'])]
    public function deleteLog(ContentPath $log, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $em->remove($log);
        $em->flush();
        $this->addFlash('success', 'Log entry deleted successfully.');
        return $this->redirectToRoute('app_dashboard_admin');
    }

    #[Route('/admin/logs/export/pdf', name: 'app_admin_logs_pdf')]
    public function exportLogsPdf(ContentPathRepository $logRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $logs = $logRepo->findBy([], ['accessedAt' => 'DESC']);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $html = $this->renderView('admin/logs_pdf.html.twig', ['logs' => $logs]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="mentis_access_logs.pdf"',
        ]);
    }

    #[Route('/admin/logs/export/excel', name: 'app_admin_logs_excel')]
    public function exportLogsExcel(ContentPathRepository $logRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $logs = $logRepo->findBy([], ['accessedAt' => 'DESC']);

        $csv = "ID,User,Email,Content Title,Accessed At\n";
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s\n",
                $log->getId(),
                $log->getUser()->getFirstname() . ' ' . $log->getUser()->getLastname(),
                $log->getUser()->getEmail(),
                str_replace(',', ' ', $log->getContentNode()->getTitle()),
                $log->getAccessedAt()->format('Y-m-d H:i:s')
            );
        }

        return new Response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="mentis_access_logs.csv"',
        ]);
    }

    // ==================== STATISTICS HELPER ====================
    
    private function calculateStats(array $users): array
    {
        $total       = count($users);
        $ages        = [];
        $genderCount = ['male' => 0, 'female' => 0, 'other' => 0];
        $ageGroups   = ['0-18' => 0, '19-30' => 0, '31-45' => 0, '46-60' => 0, '60+' => 0];

        foreach ($users as $user) {
            $age = $user->getAge();
            if ($age !== null) {
                $ages[] = $age;
                if ($age <= 18)     $ageGroups['0-18']++;
                elseif ($age <= 30) $ageGroups['19-30']++;
                elseif ($age <= 45) $ageGroups['31-45']++;
                elseif ($age <= 60) $ageGroups['46-60']++;
                else                $ageGroups['60+']++;
            }

            $gender = $user->getGender();
            if ($gender && isset($genderCount[$gender])) {
                $genderCount[$gender]++;
            }
        }

        return [
            'total'      => $total,
            'averageAge' => !empty($ages) ? round(array_sum($ages) / count($ages), 1) : 0,
            'ageGroups'  => $ageGroups,
            'gender'     => $genderCount,
            'minAge'     => !empty($ages) ? min($ages) : 0,
            'maxAge'     => !empty($ages) ? max($ages) : 0,
        ];
    }
}