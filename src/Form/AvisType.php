<?php

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'choices' => [
                    '1 *' => 1,
                    '2 **' => 2,
                    '3 ***' => 3,
                    '4 ****' => 4,
                    '5 *****' => 5,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Votre note',
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir une note.'),
                    new Range(['min' => 1, 'max' => 5]),
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaire',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Votre commentaire (optionnel)...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Avis::class]);
    }
}