<?php

namespace App\Form;

use App\Entity\Goal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Goal Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your goal title',
                ],
                'help' => 'A clear and concise title for your goal',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describe your goal in detail...',
                ],
                'help' => 'Provide a detailed description of what you want to achieve',
            ])
            ->add('deadline', DateType::class, [
                'label' => 'Deadline',
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'help' => 'Set a deadline for your goal',
            ])
            ->add('isCompleted', CheckboxType::class, [
                'label' => 'Mark as Completed',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'help' => 'Check this box if you have completed this goal',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Goal::class,
            'attr' => ['class' => 'form-horizontal'],
        ]);
    }
}
