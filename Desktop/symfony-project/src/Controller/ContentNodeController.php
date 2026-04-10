<?php

namespace App\Controller;

use App\Entity\ContentNode;
use App\Entity\ContentPath;
use App\Entity\User;
use App\Form\ContentNodeType;
use App\Repository\ContentNodeRepository;
use App\Repository\ContentPathRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/content')]
class ContentNodeController extends AbstractController
{
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
        // FOR PATIENTS - Uses the new method
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

    #[Route('/new', name: 'content_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ContentNodeRepository $contentNodeRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
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

            // Handle PDF file upload
            $pdfFile = $form->get('pdfPath')->getData();
            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $originalFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();
                $pdfFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $safeFilename
                );
                $contentNode->setPdfPath('/uploads/' . $safeFilename);
            }

            $contentNode->setAssignedUsers(array_map(fn($u) => $u->getId(), $assignedUsers->toArray()));
            $contentNode->setCreatedBy($user);
            $contentNode->setCreatedAt(new \DateTime());

            $em->persist($contentNode);
            $em->flush();

            $this->addFlash('success', 'Content item created successfully.');
            return $this->redirectToRoute('content_index');
        }

        return $this->render('content/new.html.twig', [
            'contentNode' => $contentNode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'content_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
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

        $roles = $user->getRoles();
        $assignedToMe = in_array($user->getId(), $contentNode->getAssignedUsers());

        if (!in_array('ROLE_ADMIN', $roles)
            && !in_array('ROLE_PSYCHOLOGIST', $roles)
            && !$assignedToMe
        ) {
            $this->addFlash('warning', 'You are not assigned this content.');
            return $this->redirectToRoute('content_index');
        }

        // Log access for patient + any other.
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
            'contentNode' => $contentNode,
            'assignedUsers' => $assignedUsers,
        ]);
    }

    #[Route('/{id}/edit', name: 'content_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ContentNode $contentNode,
        UserRepository $userRepository,
        EntityManagerInterface $em
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

        $form = $this->createForm(ContentNodeType::class, $contentNode);

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

            // Handle PDF file upload
            $pdfFile = $form->get('pdfPath')->getData();
            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $originalFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();
                $pdfFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $safeFilename
                );
                $contentNode->setPdfPath('/uploads/' . $safeFilename);
            }

            $contentNode->setAssignedUsers(array_map(fn($u) => $u->getId(), $assignedUsersArray));

            $em->flush();
            $this->addFlash('success', 'Content updated successfully.');

            return $this->redirectToRoute('content_index');
        }

        return $this->render('content/edit.html.twig', [
            'contentNode' => $contentNode,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'content_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, ContentNode $contentNode, EntityManagerInterface $em): Response
    {
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
            $this->addFlash('success', 'Content removed successfully.');
        }

        return $this->redirectToRoute('content_index');
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