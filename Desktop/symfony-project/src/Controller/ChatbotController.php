<?php

namespace App\Controller;

use App\Service\AIChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ChatbotController extends AbstractController
{
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
            $result = $this->aiChatService->chat($userPrompt, 'patient', 'mental health support and general well-being');

            if ($result['success']) {
                $answer = $result['message'];
            } else {
                $this->addFlash('danger', $result['error'] ?? 'AI assistant is unavailable.');
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

            $result = $this->aiChatService->summarizeContent($title, $description);
            if ($result['success']) {
                return new JsonResponse(['summary' => $result['summary']]);
            }

            $this->logger->error('Groq summary error: ' . ($result['error'] ?? 'Unknown error'));
            return new JsonResponse(['error' => $result['error'] ?? 'No summary generated'], 500);
        } catch (\Exception $e) {
            $this->logger->error('Summarize endpoint error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}

