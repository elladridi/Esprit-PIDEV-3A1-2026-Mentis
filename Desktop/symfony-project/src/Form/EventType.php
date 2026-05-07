<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Event Title *',
                'attr' => ['placeholder' => 'Enter event title...'],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Describe your event...'],
            ])
            ->add('eventType', ChoiceType::class, [
                'label' => 'Event Type',
                'choices' => [
                    'Workshop' => 'WORKSHOP',
                    'Group Therapy' => 'GROUP_THERAPY',
                    'Seminar' => 'SEMINAR',
                    'Social Event' => 'SOCIAL',
                ],
            ])
            ->add('dateTime', DateTimeType::class, [
                'label' => 'Date & Time *',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Date and time are required']),
                    new GreaterThan(['value' => 'today', 'message' => 'Event date must be in the future']),  // ONLY ONE!
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => ['placeholder' => 'Enter venue address or "Online"'],
            ])
            ->add('maxParticipants', TextType::class, [
                'label' => 'Max Participants *',
                'attr' => ['placeholder' => 'e.g., 50'],
                'constraints' => [
                    new NotBlank(['message' => 'Max participants is required']),
                    new Positive(['message' => 'Must be a positive number']),  // ONLY ONE!
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'USD',
                'required' => false,
                'attr' => ['placeholder' => '0.00'],
                'constraints' => [
                    new Range(['min' => 0, 'minMessage' => 'Price cannot be negative']),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Upcoming' => 'UPCOMING',
                    'Ongoing' => 'ONGOING',
                    'Completed' => 'COMPLETED',
                    'Cancelled' => 'CANCELLED',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}