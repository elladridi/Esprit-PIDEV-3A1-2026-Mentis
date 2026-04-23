<?php

namespace App\Entity;

use App\Repository\AssessmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

// ═══════════════════════════════════════════════════════
//  ENTITY
// ═══════════════════════════════════════════════════════
=======
>>>>>>> my-work-backup

#[ORM\Entity(repositoryClass: AssessmentRepository::class)]
#[ORM\Table(name: 'assessment')]
class Assessment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'assessment_id', type: 'integer')]
    private ?int $assessmentId = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'type', type: 'string', length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'status', type: 'string', length: 50, nullable: true)]
    private ?string $status = 'Active';

    #[ORM\Column(name: 'image_path', type: 'string', length: 500, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'assessment', cascade: ['remove'])]
    private Collection $questions;

    #[ORM\OneToMany(targetEntity: AssessmentResult::class, mappedBy: 'assessment')]
    private Collection $results;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
<<<<<<< HEAD
        $this->results   = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // ── Getters & Setters ────────────────────────────────

=======
        $this->results = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

>>>>>>> my-work-backup
    public function getAssessmentId(): ?int
    {
        return $this->assessmentId;
    }

    public function setAssessmentId(int $assessmentId): self
    {
        $this->assessmentId = $assessmentId;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setAssessment($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getAssessment() === $this) {
                $question->setAssessment(null);
            }
        }
        return $this;
    }

    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(AssessmentResult $result): self
    {
        if (!$this->results->contains($result)) {
            $this->results->add($result);
            $result->setAssessment($this);
        }
        return $this;
    }

    public function removeResult(AssessmentResult $result): self
    {
        if ($this->results->removeElement($result)) {
            if ($result->getAssessment() === $this) {
                $result->setAssessment(null);
            }
        }
        return $this;
    }
<<<<<<< HEAD
}

// ═══════════════════════════════════════════════════════
//  FORM TYPE  (nested class inside same file)
// ═══════════════════════════════════════════════════════

class AssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Assessment Title',
                'attr'  => [
                    'class'       => 'form-control form-control-lg rounded-pill',
                    'placeholder' => 'e.g. GAD-7 Anxiety Assessment',
                    'style'       => 'border: 2px solid #50C878; padding: 14px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'   => 'Assessment Type',
                'choices' => [
                    'Depression' => 'Depression',
                    'Anxiety'    => 'Anxiety',
                    'Stress'     => 'Stress',
                    'Wellness'   => 'Wellness',
                    'Sleep'      => 'Sleep',
                    'General'    => 'General',
                    'Custom'     => 'Custom',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg rounded-pill',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Draft'    => 'Draft',
                    'Active'   => 'Active',
                    'Inactive' => 'Inactive',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg rounded-pill',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control rounded-3',
                    'rows'        => 5,
                    'placeholder' => 'Brief description of what this assessment measures...',
                    'style'       => 'border: 2px solid #e0e0e0; padding: 15px; resize: vertical;',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Featured Image',
                'required' => false,
                'mapped'   => false,
                'attr'     => [
                    'class'  => 'form-control rounded-pill',
                    'accept' => 'image/*',
                    'style'  => 'border: 2px solid #50C878;',
                ],
                'constraints' => [
                    new File([
                        'maxSize'          => '5M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, GIF, WebP)',
                        'maxSizeMessage'   => 'Image must be less than 5MB',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assessment::class,
        ]);
    }
=======
>>>>>>> my-work-backup
}