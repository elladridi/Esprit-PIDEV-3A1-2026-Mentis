<?php

namespace App\Form;

use App\Entity\SessionReview;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class SessionReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Rating',
                'choices' => [
                    '⭐ 1 Star' => 1,
                    '⭐⭐ 2 Stars' => 2,
                    '⭐⭐⭐ 3 Stars' => 3,
                    '⭐⭐⭐⭐ 4 Stars' => 4,
                    '⭐⭐⭐⭐⭐ 5 Stars' => 5,
                ],
                'attr' => [
                    'class' => 'form-select rounded-pill',
                    'style' => 'border: 2px solid #50C878; padding: 12px 20px;',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please select a rating']),
                    new Range(['min' => 1, 'max' => 5]),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Your Review',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'rows' => 5,
                    'placeholder' => 'Share your experience with this session...',
                    'style' => 'border: 2px solid #e0e0e0; padding: 15px; resize: vertical;',
                ],
                'constraints' => [
                    new Length(['max' => 1000, 'maxMessage' => 'Your review cannot exceed 1000 characters']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SessionReview::class,
        ]);
    }
}