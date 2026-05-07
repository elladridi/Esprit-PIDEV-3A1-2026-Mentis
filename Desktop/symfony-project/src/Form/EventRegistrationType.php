<?php

namespace App\Form;

use App\Entity\EventRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userName', TextType::class, [
                'label' => 'Full Name *',
                'attr' => [
                    'placeholder' => 'Enter your full name',
                    'class' => 'mint-text-field'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address *',
                'attr' => [
                    'placeholder' => 'your@email.com',
                    'class' => 'mint-text-field'
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'placeholder' => '+216 12-345-678',
                    'class' => 'mint-text-field'
                ]
            ])
            ->add('ticketType', ChoiceType::class, [
                'label' => 'Ticket Type',
                'choices' => [
                    'Standard' => 'STANDARD',
                    'VIP (+50%)' => 'VIP',
                ],
                'data' => 'STANDARD',
                'required' => true,
                'attr' => ['class' => 'mint-combo-box'],
            ])
            ->add('numberOfTickets', IntegerType::class, [
                'label' => 'Number of Tickets',
                'data' => 1,
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'max' => 10,
                    'class' => 'mint-number-field'
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Credit Card' => 'CREDIT_CARD',
                    'PayPal' => 'PAYPAL',
                    'Bank Transfer' => 'BANK_TRANSFER',
                    'Cash' => 'CASH',
                ],
                'data' => 'CREDIT_CARD',
                'required' => true,
                'attr' => ['class' => 'mint-combo-box'],
            ])
            ->add('specialRequests', TextareaType::class, [
                'label' => 'Special Requests or Notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Any special requirements?',
                    'rows' => 3,
                    'class' => 'mint-text-area'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRegistration::class,
        ]);
    }
}