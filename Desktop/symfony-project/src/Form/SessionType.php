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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
                    new Callback([
                        'callback' => function($date, ExecutionContextInterface $context) {
                            if ($date && $date < new \DateTime('today')) {
                                $context->buildViolation('Session date cannot be in the past!')
                                    ->addViolation();
                            }
                        }
                    ]),
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
                    new Callback([
                        'callback' => function($endTime, ExecutionContextInterface $context) {
                            $form = $context->getRoot();
                            $startTime = $form->get('startTime')->getData();
                            
                            if ($startTime && $endTime && $startTime >= $endTime) {
                                $context->buildViolation('End time must be after start time!')
                                    ->addViolation();
                            }
                        }
                    ]),
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
                    new Range(['min' => 1, 'max' => 100, 'notInRangeMessage' => 'Number of participants must be between 1 and 100.']),
                    new Callback([
                        'callback' => function($maxParticipants, ExecutionContextInterface $context) {
                            if ($maxParticipants !== null && $maxParticipants <= 0) {
                                $context->buildViolation('Max participants must be greater than 0!')
                                    ->addViolation();
                            }
                        }
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
                    new Range(['min' => 0, 'minMessage' => 'Price cannot be negative']),
                    new Callback([
                        'callback' => function($price, ExecutionContextInterface $context) {
                            if ($price !== null && $price < 0) {
                                $context->buildViolation('Price cannot be negative!')
                                    ->addViolation();
                            }
                        }
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
            'validation_groups' => ['Default'],
        ]);
    }
}