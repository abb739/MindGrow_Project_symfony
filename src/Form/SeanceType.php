<?php

namespace App\Form;

use App\Entity\Seance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        $builder
            ->add('titre', ChoiceType::class, [
                'choices' => [
                    'Yoga détente matinal' => 'Yoga détente matinal',
                    'Méditation guidée anti-stress' => 'Méditation guidée anti-stress',
                    'Yoga dynamique soir' => 'Yoga dynamique soir',
                    'Méditation pleine conscience débutant' => 'Méditation pleine conscience débutant',
                    'Séance de respiration profonde' => 'Séance de respiration profonde',
                    'Cohérence cardiaque express' => 'Cohérence cardiaque express',
                    'Yoga doux pour seniors' => 'Yoga doux pour seniors',
                    'Yoga postural et alignement' => 'Yoga postural et alignement',
                    'Méditation pour sommeil réparateur' => 'Méditation pour sommeil réparateur',
                    'Yoga restauratif' => 'Yoga restauratif',
                ],
                'placeholder' => 'Choisissez un titre',
                'required' => true,
            ])
            ->add('description', TextType::class, [
                'required' => true,
            ])
            ->add('lieu', TextType::class, [
                'required' => true,
            ])
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('capacite', IntegerType::class, [
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG, JPEG)',
                'mapped' => false,
                'required' => !$isEdit, // Requis seulement lors de la création
                'constraints' => $isEdit ? [] : [ // Pas de contraintes si c'est une édition
                    new Assert\File([
                        'maxSize' => '1024k',
                        'mimeTypes' => ['image/jpeg', 'image/jpg'],
                        'mimeTypesMessage' => 'Le fichier doit être au format JPG ou JPEG',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 1 Mo',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
            'is_edit' => false,
        ]);
    }
}
