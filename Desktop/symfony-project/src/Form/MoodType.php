<?php

namespace App\Form;

use App\Entity\Mood;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feeling', ChoiceType::class, [
                'choices' => [
                    'Very Happy' => 'very_happy',
                    'Happy' => 'happy',
                    'Neutral' => 'neutral',
                    'Sad' => 'sad',
                    'Very Sad' => 'very_sad',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('note', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Tell us more about how you feel...']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mood::class,
        ]);
    }
}
