<?php

namespace App\Form;

use App\Entity\Assessment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class AssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Assessment Title',
                'attr'  => [
                    'class'       => 'form-control form-control-lg rounded-pill',
                    'placeholder' => 'e.g. GAD-7 Anxiety Assessment',
                    'style'       => 'border: 2px solid #50C878; padding: 14px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'   => 'Assessment Type',
                'choices' => [
                    'Depression' => 'Depression',
                    'Anxiety'    => 'Anxiety',
                    'Stress'     => 'Stress',
                    'Wellness'   => 'Wellness',
                    'Sleep'      => 'Sleep',
                    'General'    => 'General',
                    'Custom'     => 'Custom',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg rounded-pill',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Draft'    => 'Draft',
                    'Active'   => 'Active',
                    'Inactive' => 'Inactive',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg rounded-pill',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control rounded-3',
                    'rows'        => 5,
                    'placeholder' => 'Brief description of what this assessment measures...',
                    'style'       => 'border: 2px solid #e0e0e0; padding: 15px; resize: vertical;',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Featured Image',
                'required' => false,
                'mapped'   => false,
                'attr'     => [
                    'class'  => 'form-control rounded-pill',
                    'accept' => 'image/*',
                    'style'  => 'border: 2px solid #50C878;',
                ],
                'constraints' => [
                    new File([
                        'maxSize'          => '5M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, GIF, WebP)',
                        'maxSizeMessage'   => 'Image must be less than 5MB',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assessment::class,
        ]);
    }
}