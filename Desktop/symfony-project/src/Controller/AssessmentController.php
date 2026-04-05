<?php

namespace App\Controller;

use App\Entity\Assessment;
use App\Repository\AssessmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/assessment')]
class AssessmentController extends AbstractController
{
    private EntityManagerInterface $em;
    private AssessmentRepository $repo;

    public function __construct(EntityManagerInterface $em, AssessmentRepository $repo)
    {
        $this->em = $em;
        $this->repo = $repo;
    }

    // ── LIST ALL ──────────────────────────────────────────────────
    #[Route('/', name: 'assessment_index', methods: ['GET'])]
    public function index(): Response
    {
        $assessments = $this->repo->findAll();
        return $this->render('assessment/index.html.twig', [
            'assessments' => $assessments,
        ]);
    }

    // ── LIST ACTIVE ONLY ──────────────────────────────────────────
    #[Route('/active', name: 'assessment_active', methods: ['GET'])]
    public function active(): Response
    {
        $assessments = $this->repo->findAllActive();
        return $this->render('assessment/active.html.twig', [
            'assessments' => $assessments,
        ]);
    }

    // ── CREATE FORM ───────────────────────────────────────────────
    #[Route('/new', name: 'assessment_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $assessment = new Assessment();
            $assessment->setTitle($request->request->get('title'));
            $assessment->setDescription($request->request->get('description'));
            $assessment->setType($request->request->get('type'));
            $assessment->setStatus($request->request->get('status', 'Active'));
            $assessment->setCreatedAt(new \DateTime());

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $imagePath = $this->handleImageUpload($imageFile);
                $assessment->setImagePath($imagePath);
            }

            $this->em->persist($assessment);
            $this->em->flush();

            $this->addFlash('success', 'Assessment created successfully!');
            return $this->redirectToRoute('assessment_index');
        }

        return $this->render('assessment/new.html.twig');
    }

    // ── VIEW SINGLE ───────────────────────────────────────────────
    #[Route('/{id}', name: 'assessment_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $assessment = $this->repo->find($id);

        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        return $this->render('assessment/show.html.twig', [
            'assessment' => $assessment,
        ]);
    }

    // ── EDIT FORM ─────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'assessment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $assessment = $this->repo->find($id);

        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        if ($request->isMethod('POST')) {
            $assessment->setTitle($request->request->get('title'));
            $assessment->setDescription($request->request->get('description'));
            $assessment->setType($request->request->get('type'));
            $assessment->setStatus($request->request->get('status'));

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $imagePath = $this->handleImageUpload($imageFile);
                $assessment->setImagePath($imagePath);
            }

            $this->em->flush();

            $this->addFlash('success', 'Assessment updated successfully!');
            return $this->redirectToRoute('assessment_index');
        }

        return $this->render('assessment/edit.html.twig', [
            'assessment' => $assessment,
        ]);
    }

    // ── DELETE ────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'assessment_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $assessment = $this->repo->find($id);

        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        $this->em->remove($assessment);
        $this->em->flush();

        $this->addFlash('success', 'Assessment deleted successfully!');
        return $this->redirectToRoute('assessment_index');
    }

    // ── TOGGLE STATUS ─────────────────────────────────────────────
    #[Route('/{id}/toggle-status', name: 'assessment_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id): Response
    {
        $assessment = $this->repo->find($id);

        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        $newStatus = $assessment->getStatus() === 'Active' ? 'Inactive' : 'Active';
        $assessment->setStatus($newStatus);
        $this->em->flush();

        $this->addFlash('success', 'Status updated to: ' . $newStatus);
        return $this->redirectToRoute('assessment_index');
    }

    // ── SEARCH BY TYPE ────────────────────────────────────────────
    #[Route('/search/type', name: 'assessment_search_type', methods: ['GET'])]
    public function searchByType(Request $request): Response
    {
        $type = $request->query->get('type', '');
        $assessments = $this->repo->findByType($type);

        return $this->render('assessment/index.html.twig', [
            'assessments' => $assessments,
            'searchType' => $type,
        ]);
    }

    // ── PRIVATE: Handle Image Upload ──────────────────────────────
    private function handleImageUpload($imageFile): string
    {
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/assessment_images';

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $extension = $imageFile->guessExtension();
        $newFileName = 'assessment_' . time() . '.' . $extension;
        $imageFile->move($uploadsDir, $newFileName);

        return 'assessment_images/' . $newFileName;
    }
    
}