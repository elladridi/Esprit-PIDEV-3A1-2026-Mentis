<?php

namespace App\Form;

use App\Entity\Session;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Positive;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Session Title',
                'attr' => [
                    'class' => 'form-control form-control-lg rounded-pill',
                    'placeholder' => 'e.g., Mindfulness and Relaxation Session',
                    'style' => 'border: 2px solid #50C878; padding: 14px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['max' => 255, 'maxMessage' => 'Title cannot exceed 255 characters']),
                ],
            ])
            ->add('sessionDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control form-control-lg rounded-pill',
                    'style' => 'border: 2px solid #50C878; padding: 12px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Date is required']),
                ],
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Start Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control form-control-lg rounded-pill',
                    'style' => 'border: 2px solid #50C878; padding: 12px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Start time is required']),
                ],
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'End Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control form-control-lg rounded-pill',
                    'style' => 'border: 2px solid #50C878; padding: 12px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'End time is required']),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => [
                    'class' => 'form-control form-control-lg rounded-pill',
                    'placeholder' => 'Room, address, or online link',
                    'style' => 'border: 2px solid #50C878; padding: 14px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Location is required']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('sessionType', ChoiceType::class, [
                'label' => 'Session Type',
                'choices' => [
                    'Individual' => 'Individual',
                    'Group' => 'Group',
                    'Family' => 'Family',
                    'Couple' => 'Couple',
                    'Online' => 'Online',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg rounded-pill',
                    'style' => 'border: 2px solid #50C878;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Session type is required']),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Scheduled' => 'scheduled',
                    'Active' => 'active',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg rounded-pill',
                    'style' => 'border: 2px solid #e0e0e0;',
                ],
            ])
            ->add('category', TextType::class, [
                'label' => 'Category',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rounded-pill',
                    'placeholder' => 'e.g., Anxiety, Stress, General',
                    'style' => 'border: 2px solid #e0e0e0; padding: 12px 20px;',
                ],
            ])
            ->add('maxParticipants', NumberType::class, [
                'label' => 'Max Participants',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rounded-pill',
                    'style' => 'border: 2px solid #e0e0e0; padding: 12px 20px;',
                ],
                'constraints' => [
                    new Positive(['message' => 'Max participants must be a positive number']),
                    // FIXED: Use notInRangeMessage instead of minMessage/maxMessage
                    new Range([
                        'min' => 1, 
                        'max' => 100, 
                        'notInRangeMessage' => 'Max participants must be between {{ min }} and {{ max }}.'
                    ]),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price ($)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rounded-pill',
                    'style' => 'border: 2px solid #e0e0e0; padding: 12px 20px;',
                ],
                'constraints' => [
                    // FIXED: Use notInRangeMessage for single min constraint
                    new Range([
                        'min' => 0,
                        'notInRangeMessage' => 'Price cannot be negative.'
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}