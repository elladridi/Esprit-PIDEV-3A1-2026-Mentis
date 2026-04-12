<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter your first name']),
                    new Assert\Length(['min' => 2, 'max' => 50]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your last name'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter your last name']),
                    new Assert\Length(['min' => 2, 'max' => 50]),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'attr' => ['class' => 'form-control', 'placeholder' => '+1234567890'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter your phone number']),
                    new Assert\Regex([
                        'pattern' => '/^[\+][0-9]{1,3}[0-9]{7,15}$|^[0-9]{8,15}$/',
                        'message' => 'Please enter a valid phone number (+1234567890 or 12345678)',
                    ]),
                ],
            ])
            ->add('dateofbirth', TextType::class, [
                'label' => 'Date of Birth',
                'attr' => ['class' => 'form-control', 'type' => 'date'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter your date of birth']),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                    'Other' => 'other',
                ],
                'attr' => ['class' => 'form-select'],
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
                'attr' => ['class' => 'form-select'],
            ])
            ->add('cvFile', FileType::class, [
                'label' => 'CV (PDF) - For Psychologists',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'application/pdf'],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ]
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
                    new Assert\Length(['min' => 6]),
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