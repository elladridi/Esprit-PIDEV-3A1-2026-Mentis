<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your first name'],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your last name'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'attr' => ['class' => 'form-control', 'placeholder' => '+1234567890'],
            ])
            ->add('dateofbirth', TextType::class, [
                'label' => 'Date of Birth (YYYY-MM-DD)',
                'attr' => ['class' => 'form-control', 'placeholder' => '1990-01-01', 'type' => 'date'],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                    'Other' => 'other',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'your@email.com'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Account Type',
                'choices' => [
                    'Patient' => 'Patient',
                    'Psychologist' => 'Psychologist',
                    'Admin' => 'Admin',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Enter password'],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Confirm password'],
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Password is required']),
                    new Assert\Length(['min' => 6, 'minMessage' => 'Password must be at least {{ limit }} characters']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}