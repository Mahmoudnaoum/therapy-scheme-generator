<?php

namespace App\Form\Therapy;

use App\Entity\Therapy\Label;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shortName', TextType::class, [
                'label' => 'app-therapy-label-edit-form-short-name',
            ])
            ->add('reportName', TextType::class, [
                'label' => 'app-therapy-label-edit-form-report-name',
            ])
            ->add('stubsOrder', TextType::class, [
                'label' => false,
                'mapped' => false,
                'attr' => ['style' => 'display:none', 'readonly' => true],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'app-save-button',
            ])
            ->add('delete', ButtonType::class, [
                'label' => 'app-delete-button',
                'attr' => [
                    'class' => 'btn-danger btn',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#confirmModal'
                ]
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Label::class
        ]);
    }
}
