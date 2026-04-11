<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; // Add this
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

// ═══════════════════════════════════════════════════════
//  ENTITY
// ═══════════════════════════════════════════════════════

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'question')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'question_id', type: 'integer')]
    private ?int $questionId = null;

    #[ORM\Column(name: 'text', type: 'text')]
    private ?string $text = null;

    #[ORM\Column(name: 'scale', type: 'string', length: 255, nullable: true)]
    private ?string $scale = null;

    #[ORM\ManyToOne(targetEntity: Assessment::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'assessment_id', referencedColumnName: 'assessment_id')]
    private ?Assessment $assessment = null;

    // ── Getters & Setters ────────────────────────────────

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    public function setQuestionId(int $questionId): self
    {
        $this->questionId = $questionId;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getScale(): ?string
    {
        return $this->scale;
    }

    public function setScale(?string $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    public function getAssessment(): ?Assessment
    {
        return $this->assessment;
    }

    public function setAssessment(?Assessment $assessment): self
    {
        $this->assessment = $assessment;
        return $this;
    }
}

// ═══════════════════════════════════════════════════════
//  FORM TYPE  (nested class inside same file)
// ═══════════════════════════════════════════════════════

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assessment', EntityType::class, [
                'class'        => Assessment::class,
                'choice_label' => fn(Assessment $a) => $a->getTitle() . ' (' . $a->getType() . ') - ' . $a->getStatus(),
                'placeholder'  => '-- Select Assessment --',
                'required'     => true,
                'label'        => 'Assessment',
                'attr'         => [
                    'class' => 'form-select form-select-lg rounded-pill',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please select an assessment']),
                ],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Question Text',
                'attr'  => [
                    'class'       => 'form-control rounded-3',
                    'rows'        => 5,
                    'placeholder' => 'Enter the question text here...',
                    'style'       => 'border: 2px solid #e0e0e0; padding: 15px; resize: vertical;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Question text is required']),
                    new Length(['max' => 1000]),
                ],
            ])
            ->add('scale', ChoiceType::class, [  // Changed from TextType to ChoiceType
                'label'    => 'Answer Scale',
                'required' => false,
                'choices'  => [
                    'Never / Rarely / Sometimes / Often / Always' => 'Never/Rarely/Sometimes/Often/Always',
                    '1-5 Numeric Scale (1=Lowest, 5=Highest)' => '1-5 numeric',
                    'Yes / No' => 'Yes/No',
                    '1=Never, 2=Rarely, 3=Sometimes, 4=Often, 5=Always' => '1=Never,2=Rarely,3=Sometimes,4=Often,5=Always',
                    'Strongly Disagree / Disagree / Neutral / Agree / Strongly Agree' => 'Strongly Disagree/Disagree/Neutral/Agree/Strongly Agree',
                    'Very Poor / Poor / Average / Good / Excellent' => 'Very Poor/Poor/Average/Good/Excellent',
                    '1-10 Numeric Scale (1=Lowest, 10=Highest)' => '1-10 numeric',
                    'Not Important / Slightly Important / Moderately Important / Very Important / Extremely Important' => 'Not Important/Slightly Important/Moderately Important/Very Important/Extremely Important',
                ],
                'placeholder' => '-- Select a scale type --',
                'attr'     => [
                    'class' => 'form-select form-select-lg rounded-pill',
                    'style' => 'border: 2px solid #e0e0e0; padding: 14px 20px;',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}