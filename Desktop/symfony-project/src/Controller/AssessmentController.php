<?php

namespace App\Controller;

use App\Entity\Assessment;
<<<<<<< HEAD
use App\Entity\AssessmentType;
=======
use App\Form\AssessmentType;
>>>>>>> my-work-backup
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
        $this->em   = $em;
        $this->repo = $repo;
    }

    #[Route('/', name: 'assessment_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('assessment/index.html.twig', [
            'assessments' => $this->repo->findAll(),
        ]);
    }

    #[Route('/active', name: 'assessment_active', methods: ['GET'])]
    public function active(): Response
    {
        return $this->render('assessment/active.html.twig', [
            'assessments' => $this->repo->findAllActive(),
        ]);
    }

    #[Route('/new', name: 'assessment_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $assessment = new Assessment();
        $form       = $this->createForm(AssessmentType::class, $assessment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assessment->setCreatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $assessment->setImagePath($this->handleImageUpload($imageFile));
            }

            $this->em->persist($assessment);
            $this->em->flush();

            $this->addFlash('success', 'Assessment created successfully!');
            return $this->redirectToRoute('assessment_index');
        }

        return $this->render('assessment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'assessment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $assessment = $this->repo->find($id);
        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        $form = $this->createForm(AssessmentType::class, $assessment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $assessment->setImagePath($this->handleImageUpload($imageFile));
            }

            $this->em->flush();
            $this->addFlash('success', 'Assessment updated successfully!');
            return $this->redirectToRoute('assessment_show', ['id' => $id]);
        }

        return $this->render('assessment/edit.html.twig', [
            'assessment' => $assessment,
            'form'       => $form->createView(),
        ]);
    }

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

    #[Route('/{id}/toggle-status', name: 'assessment_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id): Response
    {
        $assessment = $this->repo->find($id);
        if (!$assessment) {
            throw $this->createNotFoundException('Assessment not found');
        }

        $assessment->setStatus($assessment->getStatus() === 'Active' ? 'Inactive' : 'Active');
        $this->em->flush();
        $this->addFlash('success', 'Status updated to: ' . $assessment->getStatus());
        return $this->redirectToRoute('assessment_index');
    }

    #[Route('/search/type', name: 'assessment_search_type', methods: ['GET'])]
    public function searchByType(Request $request): Response
    {
        $type = $request->query->get('type', '');
        return $this->render('assessment/index.html.twig', [
            'assessments' => $this->repo->findByType($type),
            'searchType'  => $type,
        ]);
    }

    private function handleImageUpload($imageFile): string
    {
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/assessment_images';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $newFileName = 'assessment_' . time() . '.' . $imageFile->guessExtension();
        $imageFile->move($uploadsDir, $newFileName);
        return 'assessment_images/' . $newFileName;
    }
}