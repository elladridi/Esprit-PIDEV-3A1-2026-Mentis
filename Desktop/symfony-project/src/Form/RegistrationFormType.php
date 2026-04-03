<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your first name',
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your last name',
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+1234567890',
                    'type' => 'tel',
                    'pattern' => '\\+?[0-9]{7,15}',
                    'title' => 'Use country code, e.g. +1234567890',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Phone number is required']),
                    new Assert\Regex([
                        'pattern' => '/^\\+?[0-9]{7,15}$/',
                        'message' => 'Enter a valid phone number.',
                    ]),
                ],
            ])
            ->add('dateofbirth', TextType::class, [
                'label' => 'Date of Birth',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'YYYY-MM-DD',
                    'type' => 'date',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Date of birth is required',
                    ]),
                    new Assert\Date([
                        'message' => 'Please enter a valid date in YYYY-MM-DD format',
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email',
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Account Type',
                'choices' => [
                    'Patient' => 'Patient',
                    'Psychologist' => 'Psychologist',
                    'Admin' => 'Admin',
                ],
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control',
                    'placeholder' => 'Enter your password',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Password should not be empty',
                    ]),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
