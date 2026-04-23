<?php

namespace App\Controller;

use App\Entity\ContentNode;
use App\Entity\ContentPath;
use App\Entity\User;
use App\Form\ContentNodeType;
use App\Repository\ContentNodeRepository;
use App\Repository\ContentPathRepository;
use App\Repository\UserRepository;
<<<<<<< HEAD
use App\Service\AIChatService;
use App\Service\BookRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
=======
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
>>>>>>> my-work-backup

#[Route('/content')]
class ContentNodeController extends AbstractController
{
<<<<<<< HEAD
    #[Route('', name: 'content_index', methods: ['GET'])]
    public function index(Request $request, ContentNodeRepository $contentNodeRepository, UserRepository $userRepository, BookRecommendationService $bookService, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $q = trim($request->query->get('q', ''));
        $sort = strtolower($request->query->get('sort', 'desc'));
        if (!in_array($sort, ['asc', 'desc'], true)) {
            $sort = 'desc';
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            $nodes = $contentNodeRepository->findForAdmin($q, $sort);
        } elseif (in_array('ROLE_PSYCHOLOGIST', $roles)) {
            $nodes = $contentNodeRepository->findForPsychologist($user, $q, $sort);
        } else {
            $nodes = $contentNodeRepository->findAssignedToUserPhp($user->getId());

            if ($q) {
                $nodes = array_filter($nodes, function ($node) use ($q) {
                    return stripos($node->getTitle(), $q) !== false ||
                        stripos($node->getDescription() ?? '', $q) !== false;
                });
            }

            if ($sort === 'asc') {
                usort($nodes, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
            } else {
                usort($nodes, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
            }

            $nodes = array_values($nodes);
        }

        $nodes = $paginator->paginate(
            $nodes,
            $request->query->getInt('page', 1),
            9
        );

        $assignedUserIds = [];
        foreach ($nodes as $node) {
            foreach ($node->getAssignedUsers() as $assignedUserId) {
                $assignedUserIds[] = $assignedUserId;
            }
        }
        $assignedUsers = $assignedUserIds ? $userRepository->findBy(['id' => array_unique($assignedUserIds)]) : [];
        $assignedUserMap = [];
        foreach ($assignedUsers as $assignedUser) {
            $assignedUserMap[$assignedUser->getId()] = $assignedUser;
        }

        $userType = in_array('ROLE_PSYCHOLOGIST', $roles) ? 'psychologist' : 'patient';
        $recommendations = $bookService->getRecommendations($userType);
        $chatTopic = $q ?: 'mental well-being and supportive content management';

        return $this->render('content/index.html.twig', [
            'nodes' => $nodes,
            'assignedUserMap' => $assignedUserMap,
            'searchTerm' => $q,
            'sortOrder' => $sort,
            'recommendations' => $recommendations,
            'isPsychologist' => $userType === 'psychologist',
            'is_granted_admin' => in_array('ROLE_ADMIN', $roles),
            'chatTopic' => $chatTopic,
        ]);
    }

    #[Route('/chat', name: 'content_ai_chat', methods: ['POST'])]
    public function chat(Request $request, AIChatService $aiChatService, #[Autowire(service: 'limiter.ai_chat')] RateLimiterFactory $aiChatLimiter): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $limiter = $aiChatLimiter->create((string) $user->getId());
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return $this->json([
                'success' => false,
                'error' => 'Too many requests. Please wait before sending another message.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $roles = $user->getRoles();
        $userType = in_array('ROLE_PSYCHOLOGIST', $roles) ? 'psychologist' : 'patient';

        $payload = json_decode($request->getContent(), true);
        $message = trim($payload['message'] ?? '');
        $topic = trim($payload['topic'] ?? '');

        if (!$message) {
            return $this->json(['success' => false, 'error' => 'Please enter a message.'], Response::HTTP_BAD_REQUEST);
        }

        $context = $topic ?: 'mental health content and well-being';
        $result = $aiChatService->chat($message, $userType, $context);

        if (!$result['success']) {
            return $this->json(['success' => false, 'error' => $result['error'] ?? 'AI service unavailable.']);
        }

        return $this->json(['success' => true, 'message' => $result['message']]);
    }

    #[Route('/generate-content', name: 'content_generate_content', methods: ['POST'])]
    public function generateContent(Request $request, AIChatService $aiChatService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_PSYCHOLOGIST', $roles)) {
            return $this->json(['success' => false, 'error' => 'Only psychologists can generate content.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $topic = trim($payload['topic'] ?? '');
        $contentType = trim($payload['contentType'] ?? 'article');

        if (!$topic) {
            return $this->json(['success' => false, 'error' => 'Please provide a topic for content generation.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $aiChatService->generateContent($topic, $contentType);

        if (!$result['success']) {
            return $this->json(['success' => false, 'error' => $result['error'] ?? 'Unable to generate content.']);
        }

        return $this->json(['success' => true, 'content' => $result['content']]);
    }

    #[Route('/save-generated', name: 'content_save_generated', methods: ['POST'])]
    public function saveGenerated(
        Request $request,
        EntityManagerInterface $em,
        #[Autowire(service: 'content_logs_pool')] CacheInterface $cache
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user || !array_intersect(['ROLE_ADMIN', 'ROLE_PSYCHOLOGIST'], $user->getRoles())) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        $title = trim($payload['title'] ?? '');
        $sections = $payload['sections'] ?? [];

        if (!$title || empty($sections)) {
            return $this->json(['success' => false, 'error' => 'Missing content data.'], Response::HTTP_BAD_REQUEST);
        }

        $description = "";
        foreach ($sections as $section) {
            $description .= "### " . ($section['heading'] ?? 'Section') . "\n";
            if (isset($section['paragraphs']) && is_array($section['paragraphs'])) {
                foreach ($section['paragraphs'] as $p) {
                    $description .= $p . "\n\n";
                }
            }
        }

        $contentNode = new ContentNode();
        $contentNode->setTitle($title);
        $contentNode->setDescription($description);
        $contentNode->setCreatedBy($user);
        $contentNode->setCreatedAt(new \DateTime());
        $contentNode->setAssignedUsers([]);

        $em->persist($contentNode);
        $em->flush();
        $this->invalidateLogsCache($cache);

        return $this->json([
            'success' => true,
            'message' => 'Content saved successfully!',
            'id' => $contentNode->getId(),
        ]);
    }
=======
  #[Route('', name: 'content_index', methods: ['GET'])]
public function index(Request $request, ContentNodeRepository $contentNodeRepository, UserRepository $userRepository): Response
{
    /** @var User $user */
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $q = trim($request->query->get('q', ''));
    $sort = strtolower($request->query->get('sort', 'desc'));
    if (!in_array($sort, ['asc', 'desc'], true)) {
        $sort = 'desc';
    }

    $roles = $user->getRoles();

    if (in_array('ROLE_ADMIN', $roles)) {
        $nodes = $contentNodeRepository->findForAdmin($q, $sort);
    } elseif (in_array('ROLE_PSYCHOLOGIST', $roles)) {
        $nodes = $contentNodeRepository->findForPsychologist($user, $q, $sort);
    } else {
        // POUR LES PATIENTS - Utilise la nouvelle méthode
        $nodes = $contentNodeRepository->findAssignedToUserPhp($user->getId());
        
        // Appliquer la recherche en PHP
        if ($q) {
            $nodes = array_filter($nodes, function($node) use ($q) {
                return stripos($node->getTitle(), $q) !== false || 
                       stripos($node->getDescription() ?? '', $q) !== false;
            });
        }
        
        // Appliquer le tri en PHP
        if ($sort === 'asc') {
            usort($nodes, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
        } else {
            usort($nodes, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        }
        
        // Réindexer le tableau
        $nodes = array_values($nodes);
    }

    // ... le reste du code reste identique
    // (la partie pour charger les utilisateurs assignés)
    
    // pre-load assigned users for display
    $assignedUserIds = [];
    foreach ($nodes as $node) {
        foreach ($node->getAssignedUsers() as $assignedUserId) {
            $assignedUserIds[] = $assignedUserId;
        }
    }
    $assignedUsers = $assignedUserIds ? $userRepository->findBy(['id' => array_unique($assignedUserIds)]) : [];
    $assignedUserMap = [];
    foreach ($assignedUsers as $assignedUser) {
        $assignedUserMap[$assignedUser->getId()] = $assignedUser;
    }

    return $this->render('content/index.html.twig', [
        'nodes' => $nodes,
        'assignedUserMap' => $assignedUserMap,
        'searchTerm' => $q,
        'sortOrder' => $sort,
    ]);
}
>>>>>>> my-work-backup

    #[Route('/new', name: 'content_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ContentNodeRepository $contentNodeRepository,
        UserRepository $userRepository,
<<<<<<< HEAD
        EntityManagerInterface $em,
        #[Autowire(service: 'content_logs_pool')] CacheInterface $cache
=======
        EntityManagerInterface $em
>>>>>>> my-work-backup
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !array_intersect(['ROLE_ADMIN', 'ROLE_PSYCHOLOGIST'], $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $contentNode = new ContentNode();
        $form = $this->createForm(ContentNodeType::class, $contentNode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Doctrine\Common\Collections\Collection $assignedUsers */
            $assignedUsers = $form->get('assignedUsers')->getData();

            $contentNode->setAssignedUsers(array_map(fn($u) => $u->getId(), $assignedUsers->toArray()));
            $contentNode->setCreatedBy($user);
            $contentNode->setCreatedAt(new \DateTime());

            $em->persist($contentNode);
            $em->flush();
<<<<<<< HEAD
            $this->invalidateLogsCache($cache);
=======
>>>>>>> my-work-backup

            $this->addFlash('success', 'Content item created successfully.');
            return $this->redirectToRoute('content_index');
        }

        return $this->render('content/new.html.twig', [
            'contentNode' => $contentNode,
            'form' => $form->createView(),
        ]);
    }

<<<<<<< HEAD
    #[Route('/logs', name: 'content_logs', methods: ['GET'])]
    public function logs(
        Request $request,
        ContentNodeRepository $contentNodeRepository,
        ContentPathRepository $contentPathRepository,
        UserRepository $userRepository,
        PaginatorInterface $paginator,
        #[Autowire(service: 'content_logs_pool')] CacheInterface $cache
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || (!in_array('ROLE_ADMIN', $user->getRoles()))) {
            $this->addFlash('warning', 'Access denied. You need psychologist or admin privileges.');
            return $this->redirectToRoute('app_home');
        }

        $search = trim($request->query->get('q', ''));
        $cacheKey = 'content_logs_' . md5($search);
        $fromCache = true;

        $cachedData = $cache->get($cacheKey, function (ItemInterface $item) use ($search, $user, $contentNodeRepository, $contentPathRepository, $userRepository, &$fromCache) {
            $fromCache = false;
            $item->expiresAfter(300);

            if (in_array('ROLE_PSYCHOLOGIST', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
                $nodes = $search
                    ? $contentNodeRepository->findForPsychologist($user, $search)
                    : $contentNodeRepository->findForPsychologist($user);
            } else {
                $nodes = $search
                    ? $contentNodeRepository->findForAdmin($search)
                    : $contentNodeRepository->findForAdmin();
            }

            $logs = [];
            $totalContent = 0;
            $totalAssignedGlobal = 0;
            $totalOpenedGlobal = 0;

            foreach ($nodes as $node) {
                $totalContent++;
                $paths = $contentPathRepository->findByContentNode($node);
                $openedUserIds = array_unique(array_map(fn($p) => $p->getUser()->getId(), $paths));
                $assignedUserIds = $node->getAssignedUsers();

                $notOpenedUsers = [];
                foreach ($assignedUserIds as $uid) {
                    if (!in_array($uid, $openedUserIds, true)) {
                        $patient = $userRepository->find($uid);
                        if ($patient) {
                            $notOpenedUsers[] = $patient;
                        }
                    }
                }

                $totalAssigned = count($assignedUserIds);
                $totalOpened = count(array_intersect($assignedUserIds, $openedUserIds));
                $completionRate = $totalAssigned > 0 ? round(($totalOpened / $totalAssigned) * 100, 1) : 0;

                $totalAssignedGlobal += $totalAssigned;
                $totalOpenedGlobal += $totalOpened;

                $logs[] = [
                    'nodeTitle'      => $node->getTitle(),
                    'nodeId'         => $node->getId(),
                    'completionRate' => $completionRate,
                    'totalAssigned'  => $totalAssigned,
                    'totalOpened'    => $totalOpened,
                    'notOpenedCount' => count($notOpenedUsers),
                    'notOpened'      => array_map(fn($u) => [
                        'firstname' => $u->getFirstname(),
                        'lastname'  => $u->getLastname(),
                    ], $notOpenedUsers),
                    'paths'          => array_map(fn($p) => [
                        'userFirstname' => $p->getUser()->getFirstname(),
                        'userLastname'  => $p->getUser()->getLastname(),
                        'accessedAt'    => $p->getAccessedAt()->format('M d, Y H:i'),
                    ], $paths),
                ];
            }

            $globalCompletion = ($totalAssignedGlobal > 0)
                ? round(($totalOpenedGlobal / $totalAssignedGlobal) * 100, 1)
                : 0;

            usort($logs, fn($a, $b) => $b['completionRate'] <=> $a['completionRate']);

            $topContent    = array_slice($logs, 0, 3);
            $bottomContent = array_slice($logs, -3, 3, true);

            $averageCompletion = count($logs)
                ? round(array_sum(array_column($logs, 'completionRate')) / count($logs), 1)
                : 0;

            return [
                'logs'          => $logs,
                'kpis'          => [
                    'totalContent'        => $totalContent,
                    'totalAssigned'       => $totalAssignedGlobal,
                    'totalOpened'         => $totalOpenedGlobal,
                    'overallCompletionRate' => $globalCompletion,
                    'totalNotOpened'      => $totalAssignedGlobal - $totalOpenedGlobal,
                    'averageCompletion'   => $averageCompletion,
                ],
                'topContent'    => $topContent,
                'bottomContent' => $bottomContent,
            ];
        });

        $rawAccessLogs = $contentPathRepository->findAll();
        $pagination = $paginator->paginate(
            $rawAccessLogs,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('content/logs.html.twig', [
            'logs'          => $cachedData['logs'],
            'pagination'    => $pagination,
            'searchTerm'    => $search,
            'kpis'          => $cachedData['kpis'],
            'topContent'    => $cachedData['topContent'],
            'bottomContent' => $cachedData['bottomContent'],
            'fromCache'     => $fromCache,
        ]);
    }

    /**
     * AI Insight Report — sends KPI data to Groq and returns a written analysis.
     * Called via AJAX from the logs page. Admin only.
     */
    #[Route('/logs/ai-insight', name: 'content_logs_ai_insight', methods: ['POST'])]
    public function aiInsight(Request $request, AIChatService $aiChatService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        $kpis    = $payload['kpis']    ?? [];
        $logs    = $payload['logs']    ?? [];
        $top     = $payload['top']     ?? [];
        $bottom  = $payload['bottom']  ?? [];

        // Build a rich context string from the real KPI data
        $context = "You are an expert analytics assistant for Mentis, a mental health platform. ";
        $context .= "Analyze the following content engagement data and provide a professional, actionable insight report in 4-6 sentences. ";
        $context .= "Be specific, reference actual numbers, and suggest concrete improvements.\n\n";
        $context .= "=== PLATFORM KPIs ===\n";
        $context .= "- Total content nodes: {$kpis['totalContent']}\n";
        $context .= "- Total patient assignments: {$kpis['totalAssigned']}\n";
        $context .= "- Total content opened: {$kpis['totalOpened']}\n";
        $context .= "- Overall completion rate: {$kpis['overallCompletionRate']}%\n";
        $context .= "- Patients who haven't opened any content: {$kpis['totalNotOpened']}\n";
        $context .= "- Average completion per content: {$kpis['averageCompletion']}%\n\n";

        if (!empty($top)) {
            $context .= "=== TOP PERFORMING CONTENT ===\n";
            foreach ($top as $t) {
                $context .= "- \"{$t['nodeTitle']}\": {$t['completionRate']}% ({$t['totalOpened']}/{$t['totalAssigned']} patients)\n";
            }
            $context .= "\n";
        }

        if (!empty($bottom)) {
            $context .= "=== LOWEST PERFORMING CONTENT ===\n";
            foreach ($bottom as $b) {
                $context .= "- \"{$b['nodeTitle']}\": {$b['completionRate']}% ({$b['totalOpened']}/{$b['totalAssigned']} patients)\n";
            }
            $context .= "\n";
        }

        if (!empty($logs)) {
            $context .= "=== ALL CONTENT BREAKDOWN ===\n";
            foreach ($logs as $l) {
                $context .= "- \"{$l['nodeTitle']}\": {$l['completionRate']}% completion, {$l['notOpenedCount']} patients pending\n";
            }
        }

        $message = "Based on this data, write a concise professional insight report for the platform admin. "
            . "Highlight what is working well, what needs attention, and give 2-3 specific actionable recommendations "
            . "to improve patient engagement with the content.";

        $result = $aiChatService->chat($message, 'psychologist', $context);

        if (!$result['success']) {
            return $this->json(['success' => false, 'error' => $result['error'] ?? 'AI service unavailable.']);
        }

        return $this->json(['success' => true, 'insight' => $result['message']]);
    }

    #[Route('/logs/export-pdf', name: 'content_logs_export_pdf', methods: ['GET'])]
    public function exportLogsPdf(ContentPathRepository $contentPathRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('content_index');
        }

        $rawAccessLogs = $contentPathRepository->findAll();

        $html = $this->renderView('admin/logs_pdf.html.twig', [
            'logs' => $rawAccessLogs,
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'mentis-content-logs-' . date('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/debug-assigned', name: 'debug_assigned')]
    public function debugAssigned(ContentNodeRepository $contentNodeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        $allContent = $contentNodeRepository->findAll();
        $debug = [];

        foreach ($allContent as $content) {
            $debug[] = [
                'id'                => $content->getId(),
                'title'             => $content->getTitle(),
                'assignedUsers'     => $content->getAssignedUsers(),
                'assignedUsersType' => gettype($content->getAssignedUsers()),
            ];
        }

        $assignedToUser = $contentNodeRepository->findAssignedToUser($user->getId());

        return $this->json([
            'current_user_id'            => $user->getId(),
            'current_user_email'         => $user->getEmail(),
            'all_content'                => $debug,
            'assigned_to_current_user'   => array_map(fn($c) => [
                'id'    => $c->getId(),
                'title' => $c->getTitle(),
            ], $assignedToUser),
        ]);
    }

    #[Route('/{id}', name: 'content_show', requirements: ['id' => '\d+'], methods: ['GET'])]
=======
    #[Route('/{id}', name: 'content_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
>>>>>>> my-work-backup
    public function show(
        ContentNode $contentNode,
        ContentPathRepository $contentPathRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

<<<<<<< HEAD
        $roles        = $user->getRoles();
=======
        $roles = $user->getRoles();
>>>>>>> my-work-backup
        $assignedToMe = in_array($user->getId(), $contentNode->getAssignedUsers());

        if (!in_array('ROLE_ADMIN', $roles)
            && !in_array('ROLE_PSYCHOLOGIST', $roles)
            && !$assignedToMe
        ) {
            $this->addFlash('warning', 'You are not assigned this content.');
            return $this->redirectToRoute('content_index');
        }

<<<<<<< HEAD
=======
        // Log access for patient + any other.
>>>>>>> my-work-backup
        $path = $contentPathRepository->findByUserContent($user, $contentNode);
        if (!$path) {
            $path = new ContentPath();
            $path->setUser($user);
            $path->setContentNode($contentNode);
            $path->setAccessedAt(new \DateTime());
            $em->persist($path);
            $em->flush();
        }

        $assignedUsers = [];
        foreach ($contentNode->getAssignedUsers() as $assignedUserId) {
            $foundUser = $userRepository->find($assignedUserId);
            if ($foundUser) {
                $assignedUsers[] = $foundUser;
            }
        }

        return $this->render('content/show.html.twig', [
<<<<<<< HEAD
            'contentNode'   => $contentNode,
=======
            'contentNode' => $contentNode,
>>>>>>> my-work-backup
            'assignedUsers' => $assignedUsers,
        ]);
    }

<<<<<<< HEAD
    #[Route('/{id}/edit', name: 'content_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
=======
    #[Route('/{id}/edit', name: 'content_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
>>>>>>> my-work-backup
    public function edit(
        Request $request,
        ContentNode $contentNode,
        UserRepository $userRepository,
<<<<<<< HEAD
        EntityManagerInterface $em,
        #[Autowire(service: 'content_logs_pool')] CacheInterface $cache
=======
        EntityManagerInterface $em
>>>>>>> my-work-backup
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles) && $contentNode->getCreatedBy()?->getId() !== $user->getId()) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('content_index');
        }

<<<<<<< HEAD
        $form           = $this->createForm(ContentNodeType::class, $contentNode);
=======
        $form = $this->createForm(ContentNodeType::class, $contentNode);

>>>>>>> my-work-backup
        $assignedUserIds = $contentNode->getAssignedUsers();
        if (count($assignedUserIds) > 0) {
            $existingUsers = $userRepository->findBy(['id' => $assignedUserIds]);
            $form->get('assignedUsers')->setData($existingUsers);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assignedUsers = $form->get('assignedUsers')->getData();
            if ($assignedUsers instanceof \Doctrine\Common\Collections\Collection) {
                $assignedUsersArray = $assignedUsers->toArray();
            } elseif (is_array($assignedUsers)) {
                $assignedUsersArray = $assignedUsers;
            } else {
                $assignedUsersArray = [];
            }

            $contentNode->setAssignedUsers(array_map(fn($u) => $u->getId(), $assignedUsersArray));

            $em->flush();
<<<<<<< HEAD
            $this->invalidateLogsCache($cache);
=======
>>>>>>> my-work-backup
            $this->addFlash('success', 'Content updated successfully.');

            return $this->redirectToRoute('content_index');
        }

        return $this->render('content/edit.html.twig', [
            'contentNode' => $contentNode,
<<<<<<< HEAD
            'form'        => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'content_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        ContentNode $contentNode,
        EntityManagerInterface $em,
        #[Autowire(service: 'content_logs_pool')] CacheInterface $cache
    ): Response {
=======
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'content_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, ContentNode $contentNode, EntityManagerInterface $em): Response
    {
>>>>>>> my-work-backup
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles) && $contentNode->getCreatedBy()?->getId() !== $user->getId()) {
            $this->addFlash('warning', 'Access denied.');
            return $this->redirectToRoute('content_index');
        }

        if ($this->isCsrfTokenValid('delete-content' . $contentNode->getId(), $request->request->get('_token'))) {
            $em->remove($contentNode);
            $em->flush();
<<<<<<< HEAD
            $this->invalidateLogsCache($cache);
=======
>>>>>>> my-work-backup
            $this->addFlash('success', 'Content removed successfully.');
        }

        return $this->redirectToRoute('content_index');
    }
<<<<<<< HEAD

    private function invalidateLogsCache(CacheInterface $cache): void
    {
        $cache->delete('content_logs_' . md5(''));

        if ($cache instanceof CacheItemPoolInterface) {
            $cache->clear();
        }
    }
}
=======
#[Route('/debug-assigned', name: 'debug_assigned')]
public function debugAssigned(ContentNodeRepository $contentNodeRepository): Response
{
    /** @var User $user */
    $user = $this->getUser();
    
    if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
        return $this->redirectToRoute('app_home');
    }
    
    $allContent = $contentNodeRepository->findAll();
    $debug = [];
    
    foreach ($allContent as $content) {
        $debug[] = [
            'id' => $content->getId(),
            'title' => $content->getTitle(),
            'assignedUsers' => $content->getAssignedUsers(),
            'assignedUsersType' => gettype($content->getAssignedUsers())
        ];
    }
    
    // Récupérer les contenus assignés à l'utilisateur courant
    $assignedToUser = $contentNodeRepository->findAssignedToUser($user->getId());
    
    return $this->json([
        'current_user_id' => $user->getId(),
        'current_user_email' => $user->getEmail(),
        'all_content' => $debug,
        'assigned_to_current_user' => array_map(fn($c) => [
            'id' => $c->getId(),
            'title' => $c->getTitle()
        ], $assignedToUser)
    ]);
}
    #[Route('/logs', name: 'content_logs', methods: ['GET'])]
    public function logs(Request $request,
        ContentNodeRepository $contentNodeRepository,
        ContentPathRepository $contentPathRepository,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user || (!in_array('ROLE_ADMIN', $user->getRoles()))) {
            $this->addFlash('warning', 'Access denied. You need psychologist or admin privileges.');
            return $this->redirectToRoute('app_home');
        }

        $search = trim($request->query->get('q', ''));
        
        if (in_array('ROLE_PSYCHOLOGIST', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
            $nodes = $search ? $contentNodeRepository->findForPsychologist($user, $search) : $contentNodeRepository->findForPsychologist($user);
        } else {
            $nodes = $search ? $contentNodeRepository->findForAdmin($search) : $contentNodeRepository->findForAdmin();
        }
        
        $logs = [];
        $totalContent = 0;
        $totalAssignedGlobal = 0;
        $totalOpenedGlobal = 0;

        foreach ($nodes as $node) {
            $totalContent++;
            $paths = $contentPathRepository->findByContentNode($node);
            $openedUserIds = array_unique(array_map(fn($p) => $p->getUser()->getId(), $paths));
            $assignedUserIds = $node->getAssignedUsers();

            $notOpenedUsers = [];
            foreach ($assignedUserIds as $uid) {
                if (!in_array($uid, $openedUserIds, true)) {
                    $patient = $userRepository->find($uid);
                    if ($patient) {
                        $notOpenedUsers[] = $patient;
                    }
                }
            }

            $totalAssigned = count($assignedUserIds);
            $totalOpened = count(array_intersect($assignedUserIds, $openedUserIds));
            $completionRate = $totalAssigned > 0 ? round(($totalOpened / $totalAssigned) * 100, 1) : 0;

            $totalAssignedGlobal += $totalAssigned;
            $totalOpenedGlobal += $totalOpened;

            $logs[] = [
                'node' => $node,
                'paths' => $paths,
                'notOpened' => $notOpenedUsers,
                'totalAssigned' => $totalAssigned,
                'totalOpened' => $totalOpened,
                'completionRate' => $completionRate,
            ];
        }

        $globalCompletion = ($totalAssignedGlobal > 0) ? round(($totalOpenedGlobal / $totalAssignedGlobal) * 100, 1) : 0;

        usort($logs, fn($a, $b) => $b['completionRate'] <=> $a['completionRate']);

        $topContent = array_slice($logs, 0, 3);
        $bottomContent = array_slice($logs, -3, 3, true);

        $averageCompletion = count($logs) ? round(array_sum(array_column($logs, 'completionRate')) / count($logs), 1) : 0;

        return $this->render('content/logs.html.twig', [
            'logs' => $logs,
            'searchTerm' => $search,
            'kpis' => [
                'totalContent' => $totalContent,
                'totalAssigned' => $totalAssignedGlobal,
                'totalOpened' => $totalOpenedGlobal,
                'overallCompletionRate' => $globalCompletion,
                'totalNotOpened' => $totalAssignedGlobal - $totalOpenedGlobal,
                'averageCompletion' => $averageCompletion,
            ],
            'topContent' => $topContent,
            'bottomContent' => $bottomContent,
        ]);
    }
}
>>>>>>> my-work-backup
