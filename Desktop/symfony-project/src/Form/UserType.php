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
<<<<<<< HEAD
use Symfony\Component\Form\Extension\Core\Type\DateType;
=======
>>>>>>> my-work-backup
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'First Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter first name'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'First name is required']),
                    new Assert\Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter last name'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Last name is required']),
                    new Assert\Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'email@example.com'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email is required']),
                    new Assert\Email(['message' => 'Please enter a valid email'])
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+1234567890'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Phone number is required']),
                    new Assert\Regex([
                        'pattern' => '/^[\+][0-9]{1,3}[0-9]{7,15}$|^[0-9]{8,15}$/',
                        'message' => 'Please enter a valid phone number'
                    ])
                ]
            ])
<<<<<<< HEAD

->add('dateofbirth', DateType::class, [
    'widget' => 'single_text',  // renders as a single <input type="text">
    'format' => 'yyyy-MM-dd',   // matches Flatpickr's "Y-m-d" output
    'html5'  => false,          // disables browser native date picker so Flatpickr takes over
    'label'  => 'Date of Birth',
    'required' => false,
    'attr' => [
        'class'        => 'form-control',
        'autocomplete' => 'off',
        'placeholder'  => 'Select date of birth',
    ],
])
=======
            ->add('dateofbirth', TextType::class, [
                'label' => 'Date of Birth',
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'date'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Date of birth is required']),
                    new Assert\Regex([
                        'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                        'message' => 'Please enter date in YYYY-MM-DD format'
                    ])
                ]
            ])
>>>>>>> my-work-backup
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Select Gender' => '',
                    'Male' => 'male',
                    'Female' => 'female',
                    'Other' => 'other'
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select gender'])
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter password (leave blank to keep current)'
                ],
                'constraints' => [
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Password must be at least {{ limit }} characters'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'user_form',
        ]);
    }
}