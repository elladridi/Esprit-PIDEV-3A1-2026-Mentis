<?php

namespace App\Controller;

use App\Service\AIChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

class ChatbotController extends AbstractController
{
    private const ZAI_SERVICE_URL = 'http://localhost:3000';
    private LoggerInterface $logger;
    private AIChatService $aiChatService;

    public function __construct(LoggerInterface $logger, AIChatService $aiChatService)
    {
        $this->logger = $logger;
        $this->aiChatService = $aiChatService;
    }

    #[Route('/chatbot', name: 'app_chatbot', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $answer = null;
        $userPrompt = $request->request->get('prompt', '');

        if ($request->isMethod('POST') && trim($userPrompt) !== '') {
            // Try using AIChatService first
            $result = $this->aiChatService->chat($userPrompt, 'patient', 'mental health support and general well-being');

            if ($result['success']) {
                $answer = $result['message'];
            } else {
                // Fallback to Z.ai service
                $http = HttpClient::create();

                try {
                    $response = $http->request('POST', self::ZAI_SERVICE_URL . '/api/chat', [
                        'json' => ['message' => $userPrompt],
                        'timeout' => 60,
                    ]);

                    $status = $response->getStatusCode();
                    if ($status === 200) {
                        $data = $response->toArray();
                        $answer = $data['reply'] ?? 'No response received';
                    } else {
                        $this->addFlash('danger', 'Z.ai service returned status: ' . $status);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Error connecting to Z.ai service: ' . $e->getMessage());
                }
            }
        }

        return $this->render('chatbot/index.html.twig', [
            'answer' => $answer,
            'prompt' => $userPrompt,
        ]);
    }

    #[Route('/api/summarize-content', name: 'api_summarize_content', methods: ['POST'])]
    public function summarizeContent(Request $request): JsonResponse
    {
        try {
            $this->logger->info('Summarize request received');
            
            $content = $request->getContent();
            $this->logger->info('Request content: ' . $content);
            
            $data = json_decode($content, true);
            
            if (!$data) {
                $this->logger->error('Invalid JSON received');
                return new JsonResponse(['error' => 'Invalid JSON received'], 400);
            }

            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';

            if (!$title || !$description) {
                $this->logger->warning('Title or description missing: title=' . $title . ', description=' . $description);
                return new JsonResponse(['error' => 'Title and description are required'], 400);
            }

            // Try using AIChatService first
            $result = $this->aiChatService->summarizeContent($title, $description);
            if ($result['success']) {
                return new JsonResponse(['summary' => $result['summary']]);
            }

            // Fallback to Z.ai service
            $this->logger->info('Calling Z.ai service for title: ' . $title);
            
            $http = HttpClient::create();

            $response = $http->request('POST', self::ZAI_SERVICE_URL . '/api/summarize', [
                'json' => [
                    'title' => $title,
                    'description' => $description
                ],
                'timeout' => 60,
            ]);

            $status = $response->getStatusCode();
            $this->logger->info('Z.ai response status: ' . $status);
            
            if ($status === 200) {
                $responseData = $response->toArray();
                $this->logger->info('Z.ai response received successfully');
                return new JsonResponse(['summary' => $responseData['summary'] ?? 'No summary generated']);
            } else {
                $this->logger->error('Z.ai service error: ' . $status);
                return new JsonResponse(['error' => 'Z.ai service returned status: ' . $status], $status);
            }
        } catch (\Exception $e) {
            $this->logger->error('Summarize endpoint error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}