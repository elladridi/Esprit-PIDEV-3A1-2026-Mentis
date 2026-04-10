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
                'label' => 'How are you feeling?',
                'attr' => ['class' => 'form-select'],
                'help' => 'Select your current mood',
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Additional Notes',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Share more about your mood...',
                ],
                'help' => 'Please share your thoughts about your current mood',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mood::class,
            'attr' => ['class' => 'form-horizontal'],
        ]);
    }
}
