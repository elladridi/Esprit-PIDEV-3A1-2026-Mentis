<?php

namespace App\Form;

use App\Entity\ContentNode;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ContentNodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Example: Cognitive Behavioral Therapy module',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Title is required']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describe the content purpose and takeaway',
                ],
            ])
            ->add('pdfFile', VichFileType::class, [
                'label' => 'PDF File',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid PDF file',
                    ])
                ],
            ])
            ->add('pdfPath', TextType::class, [
                'label' => 'PDF Path',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '/uploads/guides/worksheet.pdf',
                ],
            ])
            ->add('parentNode', EntityType::class, [
                'class' => ContentNode::class,
                'required' => false,
                'choice_label' => 'title',
                'placeholder' => 'No parent',
            ])
            ->add('assignedUsers', EntityType::class, [
                'class' => User::class,
                'query_builder' => fn (UserRepository $ur) => $ur->createQueryBuilder('u')->where('u.type = :patient')->setParameter('patient', 'Patient'),
                'choice_label' => fn (User $user) => sprintf('%s %s (%s)', $user->getFirstname(), $user->getLastname(), $user->getEmail()),
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-select select-users',
                    'data-placeholder' => 'Search and select users...',
                ],
                'choice_attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContentNode::class,
        ]);
    }
}