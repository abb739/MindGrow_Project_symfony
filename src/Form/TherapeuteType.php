<?php

namespace App\Form;

use App\Entity\Therapeute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File;

class TherapeuteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(['max' => 100, 'maxMessage' => 'Le nom ne doit pas dépasser 100 caractères.']),
                ],
                'attr' => ['placeholder' => 'Nom', 'required' => 'required'],
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Le prenom est obligatoire.'),
                    new Length(['max' => 100, 'maxMessage' => 'Le prénom ne doit pas dépasser 100 caractères.']),
                ],
                'attr' => ['placeholder' => 'Prenom', 'required' => 'required'],
            ])
            ->add('specialite', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Specialite'],
                'constraints' => [
                    new Length(['max' => 100, 'maxMessage' => 'Spécialité trop longue (100 caractères max).']),
                ],
            ])
            ->add('email', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'Email', 'required' => 'required', 'pattern' => '.+@.+', 'title' => 'L\'email doit contenir @'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Email(['message' => 'Email invalide.']),
                    new Regex([
                        'pattern' => '/@/',
                        'message' => 'L\'email doit contenir le symbole @.',
                    ]),
                    new Length(['max' => 150, 'maxMessage' => 'Email trop long (150 caractères max).']),
                ],
            ])
            ->add('telephone', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Telephone'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[+0-9\s\-]{6,20}$/',
                        'message' => 'Numéro de téléphone invalide (ex: +33123456789).',
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Image JPG/PNG uniquement.',
                    ]),
                ],
            ])
            ->add('certificatFile', FileType::class, [
                'label' => 'Certificat (PDF/Image)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => ['application/pdf', 'image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'PDF ou image uniquement.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Therapeute::class]);
    }
}