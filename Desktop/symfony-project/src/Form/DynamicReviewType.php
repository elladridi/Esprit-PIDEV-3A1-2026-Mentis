<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class DynamicReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $questions = $options['questions'] ?? [];
        
        foreach ($questions as $question) {
            $fieldName = 'q_' . $question['id'];
            $fieldConfig = $this->getFieldConfig($question);
            $builder->add($fieldName, $fieldConfig['type'], $fieldConfig['options']);
        }
    }

    private function getFieldConfig(array $question): array
    {
        $type = $question['type'];
        $text = $question['text'];
        $scale = $question['scale'] ?? 5;

        switch ($type) {
            case 'rating':
                return [
                    'type' => IntegerType::class,
                    'options' => [
                        'label' => $text,
                        'attr' => ['min' => 1, 'max' => $scale],
                        'required' => false,
                    ]
                ];
                
            case 'choice':
                $options = $question['options'] ?? ['Yes', 'Maybe', 'No'];
                return [
                    'type' => ChoiceType::class,
                    'options' => [
                        'label' => $text,
                        'choices' => array_combine($options, $options),
                        'expanded' => true,
                        'multiple' => false,
                        'required' => false,
                    ]
                ];
                
            case 'scale':
                return [
                    'type' => RangeType::class,
                    'options' => [
                        'label' => $text,
                        'attr' => ['min' => 1, 'max' => $scale],
                        'required' => false,
                    ]
                ];
                
            default:
                return [
                    'type' => TextareaType::class,
                    'options' => [
                        'label' => $text,
                        'required' => false,
                        'attr' => ['rows' => 3]
                    ]
                ];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'questions' => [],
            'csrf_protection' => false,
            'method' => 'POST',
        ]);
    }
}