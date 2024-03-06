<?php

namespace App\Form;

use App\Entity\Therapy\Scheme;
use App\Form\DataTransformer\ArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditTherapySchemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('selectAll', CheckboxType::class, [
                'required' => false,
                'label' => false,
                'data' => false,
                'mapped' => false
            ])
            ->add('suppress', CheckboxType::class, [
                'required' => false,
                'label' => false
            ])
            ->add('excerpt', CheckboxType::class, [
                'required' => false,
                'label' => false
            ])
            ->add('comments', TextareaType::class, [
                'required' => false,
                'label' => false,
                'attr' => ['style' => 'display:none', 'readonly' => true]
            ])
            ->add('targets', TextareaType::class, [
                'required' => false,
                'label' => false,
                'attr' => ['style' => 'display:none', 'readonly' => true]
            ])
            ->add('stubsOrder', TextareaType::class, [
                'required' => false,    
                'label' => false,
                'attr' => ['style' => 'display:none', 'readonly' => true],
            ])
            ->add("updateCurrent", CheckboxType::class, [
                'required' => false,
                'label' => false,
                'mapped' => false,
                'attr' => ['style' => 'display:none']
            ]);

        $builder
            ->get('comments')
            ->addModelTransformer(new ArrayToStringTransformer());

        $builder
            ->get('targets')
            ->addModelTransformer(new ArrayToStringTransformer());
            
        $builder
        ->get('stubsOrder')
        ->addModelTransformer(new ArrayToStringTransformer());
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Scheme::class,
        ]);
    }
}
