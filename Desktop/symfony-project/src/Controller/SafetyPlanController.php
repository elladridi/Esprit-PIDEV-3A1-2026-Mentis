<?php

namespace App\Controller;

use App\Service\GroqService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/safety-plan')]
class SafetyPlanController extends AbstractController
{
    private GroqService $groqService;

    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }

    // ── Show Builder ─────────────────────────────────────
    #[Route('/', name: 'safety_plan_index', methods: ['GET'])]
    public function index(SessionInterface $session, Request $request): Response
    {
        $plan = $session->get('safety_plan', $this->emptyPlan());

        // Read result ID from query string or session
        $resultId = $request->query->get('result_id')
            ?? $session->get('safety_plan_result_id');

        // Persist result ID in session through redirects
        if ($request->query->get('result_id')) {
            $session->set('safety_plan_result_id', $request->query->get('result_id'));
        }

        return $this->render('safety_plan/index.html.twig', [
            'plan'     => $plan,
            'resultId' => $resultId,
        ]);
    }

    // ── Save Plan to Session ─────────────────────────────
    #[Route('/save', name: 'safety_plan_save', methods: ['POST'])]
    public function save(Request $request, SessionInterface $session): Response
    {
        $resultId = $request->request->get('result_id');
        if ($resultId) {
            $session->set('safety_plan_result_id', $resultId);
        }

        $plan = [
            'warning_signs'         => $this->parseLines($request->request->get('warning_signs', '')),
            'coping_strategies'     => $this->parseLines($request->request->get('coping_strategies', '')),
            'social_distractions'   => $this->parseLines($request->request->get('social_distractions', '')),
            'reasons_to_live'       => $this->parseLines($request->request->get('reasons_to_live', '')),
            'safe_environment'      => trim($request->request->get('safe_environment', '')),
            'support_contacts'      => $this->parseContacts($request),
            'professional_contacts' => $this->parseProfessionals($request),
            'crisis_numbers'        => [
                ['label' => 'USA Crisis Line',    'number' => '988'],
                ['label' => 'Crisis Text Line',   'number' => 'Text HOME to 741741'],
                ['label' => 'Emergency Services', 'number' => '911'],
                ['label' => 'Tunisia',            'number' => '1891'],
                ['label' => 'International',      'number' => '116 123'],
            ],
            'updated_at' => (new \DateTime())->format('Y-m-d H:i'),
        ];

        $session->set('safety_plan', $plan);
        $this->addFlash('success', 'Your safety plan has been saved successfully.');

        return $this->redirectToRoute('safety_plan_index');
    }

    // ── AI Suggest Single Section ────────────────────────
    #[Route('/ai-suggest', name: 'safety_plan_ai_suggest', methods: ['POST'])]
    public function aiSuggest(Request $request): Response
    {
        $section = $request->request->get('section', '');
        $context = $request->request->get('context', '');

        $prompts = [
            'warning_signs' =>
                "List 5 specific personal warning signs that tell someone a mental health crisis "
                . "may be coming. These are internal feelings or noticeable behavior changes. "
                . ($context ? "User's current notes: {$context}. " : '')
                . "Be specific and practical. "
                . "Return ONLY a plain numbered list, one item per line. "
                . "No scale labels. No SCALE: text. Just the items.",

            'coping_strategies' =>
                "List 6 specific coping strategies someone can do alone to calm down during "
                . "emotional distress. Include breathing, movement, creative, and sensory options. "
                . ($context ? "User's current notes: {$context}. " : '')
                . "Be specific and practical. "
                . "Return ONLY a plain numbered list, one item per line. "
                . "No scale labels. No SCALE: text. Just the items.",

            'social_distractions' =>
                "List 5 social activities or places that can help distract from a mental health crisis. "
                . "Include calling people, going to places, and doing activities with others. "
                . ($context ? "User's current notes: {$context}. " : '')
                . "Return ONLY a plain numbered list, one item per line. "
                . "No scale labels. No SCALE: text. Just the items.",

            'reasons_to_live' =>
                "List 5 deeply meaningful and personal reasons to continue living. "
                . "Include relationships, pets, future goals, passions, and life experiences. "
                . ($context ? "User's current notes: {$context}. " : '')
                . "Make them emotional and specific. "
                . "Return ONLY a plain numbered list, one item per line. "
                . "No scale labels. No SCALE: text. Just the items.",

            'safe_environment' =>
                "List 4 practical steps to make a home safer during a mental health crisis. "
                . "Include asking for help, removing dangerous items, creating calm spaces. "
                . ($context ? "User's current notes: {$context}. " : '')
                . "Return ONLY a plain numbered list, one item per line. "
                . "No scale labels. No SCALE: text. Just the items.",
        ];

        if (!isset($prompts[$section])) {
            return $this->json(['error' => 'Invalid section'], 400);
        }

        try {
            $suggestions = $this->groqService->generateSafetyPlanSuggestions($prompts[$section]);
            return $this->json(['success' => true, 'suggestions' => $suggestions]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── AI Generate Full Plan ────────────────────────────
    #[Route('/ai-generate-full', name: 'safety_plan_ai_full', methods: ['POST'])]
    public function aiGenerateFull(Request $request): Response
    {
        $context = $request->request->get('context', '');

        try {
            $plan = $this->groqService->generateFullSafetyPlan($context);

            if (empty($plan)) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Could not generate plan. Please try again.',
                ]);
            }

            return $this->json([
                'success' => true,
                'plan'    => $plan,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ── Export PDF ───────────────────────────────────────
    #[Route('/export', name: 'safety_plan_export', methods: ['GET'])]
    public function export(SessionInterface $session): Response
    {
        $plan = $session->get('safety_plan', $this->emptyPlan());
        $user = $this->getUser();

        $html = $this->renderView('safety_plan/pdf.html.twig', [
            'plan' => $plan,
            'user' => $user,
            'date' => date('F j, Y'),
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'mentis-safety-plan-' . date('Ymd') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── Clear Plan ───────────────────────────────────────
    #[Route('/clear', name: 'safety_plan_clear', methods: ['POST'])]
    public function clear(SessionInterface $session): Response
    {
        $session->remove('safety_plan');
        $this->addFlash('success', 'Safety plan cleared.');
        return $this->redirectToRoute('safety_plan_index');
    }

    // ── Private Helpers ──────────────────────────────────
    private function parseLines(string $text): array
    {
        if (empty(trim($text))) return [];
        // Handle both \n and \r\n line endings
        $text  = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        return array_values(array_filter(array_map('trim', $lines)));
    }

    private function parseContacts(Request $request): array
    {
        $names    = $request->request->all('contact_name');
        $phones   = $request->request->all('contact_phone');
        $contacts = [];
        foreach ($names as $i => $name) {
            if (!empty(trim($name))) {
                $contacts[] = [
                    'name'  => trim($name),
                    'phone' => trim($phones[$i] ?? ''),
                ];
            }
        }
        return $contacts;
    }

    private function parseProfessionals(Request $request): array
    {
        $names  = $request->request->all('pro_name');
        $phones = $request->request->all('pro_phone');
        $roles  = $request->request->all('pro_role');
        $pros   = [];
        foreach ($names as $i => $name) {
            if (!empty(trim($name))) {
                $pros[] = [
                    'name'  => trim($name),
                    'phone' => trim($phones[$i] ?? ''),
                    'role'  => trim($roles[$i] ?? ''),
                ];
            }
        }
        return $pros;
    }

    private function emptyPlan(): array
    {
        return [
            'warning_signs'         => [],
            'coping_strategies'     => [],
            'social_distractions'   => [],
            'reasons_to_live'       => [],
            'safe_environment'      => '',
            'support_contacts'      => [],
            'professional_contacts' => [],
            'crisis_numbers'        => [
                ['label' => 'USA Crisis Line',    'number' => '988'],
                ['label' => 'Crisis Text Line',   'number' => 'Text HOME to 741741'],
                ['label' => 'Emergency Services', 'number' => '911'],
                ['label' => 'Tunisia',            'number' => '1891'],
                ['label' => 'International',      'number' => '116 123'],
            ],
            'updated_at' => null,
        ];
    }
}