<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your first name'],
                'constraints' => [
                    new NotBlank(['message' => 'First name is required']),
                    new Length(['min' => 2, 'max' => 50, 'minMessage' => 'First name must be at least 2 characters'])
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your last name'],
                'constraints' => [
                    new NotBlank(['message' => 'Last name is required']),
                    new Length(['min' => 2, 'max' => 50, 'minMessage' => 'Last name must be at least 2 characters'])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['class' => 'form-control', 'placeholder' => 'you@example.com'],
                'constraints' => [
                    new NotBlank(['message' => 'Email is required'])
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'attr' => ['class' => 'form-control', 'placeholder' => '+1234567890'],
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required']),
                    new Regex([
                        'pattern' => '/^[0-9+\-\s()]{8,20}$/',
                        'message' => 'Please enter a valid phone number'
                    ])
                ]
            ])
            ->add('dateofbirth', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'html5' => true,
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'form-control',
                    'max' => date('Y-m-d', strtotime('-18 years')),
                    'min' => date('Y-m-d', strtotime('-120 years')),
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Date of birth is required']),
                    new Callback([$this, 'validateAge'])
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Select Gender' => '',
                    '👨 Male' => 'male',
                    '👩 Female' => 'female',
                    '👤 Other' => 'other'
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Please select your gender'])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Account Type',
                'choices' => [
                    'Patient' => 'Patient',
                    'Psychologist' => 'Psychologist'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('cvFile', FileType::class, [
                'label' => 'CV/Resume (PDF only)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'application/pdf'],
                'constraints' => [
                    new File([
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
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a password']),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                        ])
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Confirm password']
                ],
                'invalid_message' => 'The password fields must match.',
            ]);
    }

    public function validateAge($dateofbirth, ExecutionContextInterface $context): void
    {
        if (!$dateofbirth) {
            return;
        }

        $today = new \DateTime();
        $age = $today->diff($dateofbirth)->y;

        if ($age < 18) {
            $context->buildViolation('You must be at least 18 years old to register. Your age: {{ age }} years.')
                ->setParameter('{{ age }}', $age)
                ->addViolation();
        }
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