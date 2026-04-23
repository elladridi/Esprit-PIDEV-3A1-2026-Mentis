<?php

namespace App\Form;

use App\Entity\Assessment;
use App\Entity\Question;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

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
            ->add('scale', TextType::class, [
                'label'    => 'Answer Scale',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control form-control-lg rounded-pill',
                    'placeholder' => 'e.g. Never/Rarely/Sometimes/Often/Always',
                    'style'       => 'border: 2px solid #e0e0e0; padding: 14px 20px;',
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