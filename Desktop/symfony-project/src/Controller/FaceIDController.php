<?php
// src/Controller/FaceIDController.php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Route('/face')]
class FaceIDController extends AbstractController
{
    public function __construct(private FaceRecognitionService $faceService)
    {
    }

    // -------------------------------------------------------------------------
    // REGISTER
    // -------------------------------------------------------------------------

    #[Route('/api/register/{id}', name: 'api_face_register', methods: ['POST'])]
    public function registerFaceSample(
        int $id,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found']);
        }

        // Only the user themselves or an admin can register
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $imageFile = $request->files->get('image');
        if (!$imageFile) {
            return $this->json(['success' => false, 'message' => 'No image provided']);
        }

        $result = $this->faceService->registerSample($id, $imageFile);

        if ($result['success'] && ($result['completed'] ?? false)) {
            $user->setFaceEnabled(true);
            $em->flush();
        }

        return $this->json($result);
    }

    // -------------------------------------------------------------------------
    // VERIFY / LOGIN
    // -------------------------------------------------------------------------

    #[Route('/api/verify', name: 'api_face_verify', methods: ['POST'])]
    public function verifyFace(
        Request $request,
        UserRepository $userRepo,
        EventDispatcherInterface $eventDispatcher
    ): JsonResponse {
        $imageFile = $request->files->get('image');
        if (!$imageFile) {
            return $this->json(['success' => false, 'message' => 'No image provided']);
        }

        $users = $userRepo->findBy(['faceEnabled' => true]);
        if (empty($users)) {
            return $this->json(['success' => false, 'message' => 'No users with Face ID enabled']);
        }

        $result = $this->faceService->verifyAgainstAll($users, $imageFile);

        if (!$result['success'] || !$result['user']) {
            return $this->json([
                'success'    => false,
                'message'    => $result['message'],
                'confidence' => $result['confidence'] ?? 0,
            ]);
        }

        $matchedUser = $result['user'];

        // Authenticate
        $token = new UsernamePasswordToken($matchedUser, 'main', $matchedUser->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));
        $eventDispatcher->dispatch(
            new InteractiveLoginEvent($request, $token),
            'security.interactive_login'
        );

        return $this->json([
            'success'    => true,
            'user_id'    => $matchedUser->getId(),
            'email'      => $matchedUser->getEmail(),
            'name'       => $matchedUser->getFullName(),
            'confidence' => $result['confidence'],
            'redirect'   => $this->generateUrl('app_dashboard'),
        ]);
    }

    // -------------------------------------------------------------------------
    // DISABLE
    // -------------------------------------------------------------------------

    #[Route('/api/disable/{id}', name: 'api_face_disable', methods: ['POST'])]
    public function disableFace(
        int $id,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found']);
        }

        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        // Delete stored face images via service
        $this->faceService->deleteUserFaces($id);

        $user->setFaceEnabled(false);
        $user->setFaceSamples([]);

        // Only call these if the methods exist on your entity
        if (method_exists($user, 'setFaceData')) {
            $user->setFaceData(null);
        }
        if (method_exists($user, 'setFaceRegisteredAt')) {
            $user->setFaceRegisteredAt(null);
        }

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Face ID disabled successfully']);
    }

    // -------------------------------------------------------------------------
    // DEBUG / TEST ROUTES  (remove these before going to production)
    // -------------------------------------------------------------------------

    #[Route('/debug-session', name: 'debug_session', methods: ['GET'])]
    public function debugSession(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'user_authenticated' => $user !== null,
            'user_id'            => $user?->getId(),
            'user_email'         => $user?->getUserIdentifier(),
        ]);
    }

    #[Route('/debug', name: 'face_debug', methods: ['GET'])]
    public function debug(): JsonResponse
    {
        $projectDir  = $this->getParameter('kernel.project_dir');
        $scriptPath  = $projectDir . '\\scripts\\face.py';
        $cascadePath = $projectDir . '\\public\\haarcascade_frontalface_default.xml';
        $python      = 'C:\\Users\\DELL\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe';

        return $this->json([
            'script_exists'   => file_exists($scriptPath),
            'cascade_exists'  => file_exists($cascadePath),
            'python_version'  => shell_exec('"' . $python . '" --version 2>&1'),
            'script_path'     => $scriptPath,
            'cascade_path'    => $cascadePath,
        ]);
    }

    #[Route('/register-test/{id}', name: 'face_register_test', methods: ['GET'])]
    public function registerTest(int $id): Response
    {
        return $this->render('face_register.html.twig', [
            'userId' => $id,
        ]);
    }
}