<?php

namespace App\Form\Therapy;


use App\Entity\Therapy\Label;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Repository\Therapy\LabelRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class StubType extends AbstractType
{
    private LabelRepository $labelRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(LabelRepository $labelRepository, EntityManagerInterface $entityManager)
    {
        $this->labelRepository = $labelRepository;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'app-therapy-stub-form-label-name',
            ])
            ->add('description', CKEditorType::class, [
                'label' => 'app-therapy-stub-form-label-description',
                'config' => [
                    'removePlugins' => 'print,preview,save,newpage,sourcearea,templates,exportpdf,pastefromword,scayt,forms,div,language,image,smiley,iframe,about,maximize,showblocks',
                ],
            ])
            ->add('excerpt', CKEditorType::class, [
                'label' => 'app-therapy-stub-form-label-excerpt',
                'config' => [
                    'removePlugins' => 'print,preview,save,newpage,sourcearea,templates,exportpdf,pastefromword,scayt,forms,div,language,image,smiley,iframe,about,maximize,showblocks',
                ],
            ])
            ->add('background', CKEditorType::class, [
                'label' => 'app-therapy-stub-form-label-background',
                'config' => [
                    'removePlugins' => 'print,preview,save,newpage,sourcearea,templates,exportpdf,pastefromword,scayt,forms,div,language,image,smiley,iframe,about,maximize,showblocks',
                ],
            ])
            ->add('labels', EntityType::class, [
                'label' => 'app-therapy-stub-form-label-labels',
                'attr' => ['class' => 'select2 form-control select2-widget'],
                'class' => Label::class,
                'choice_label' => 'shortName',
                'multiple' => true,
                'required' => true,
            ])
            ->add('submitAndNew', CheckboxType::class, [
                'required' => false,
                'label' => false,
                'mapped' => false,
                'attr' => ['style' => 'display: none;'],
            ]);

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function(PreSubmitEvent $event){
                $data = $event->getData();
                
                $labels = $data['labels'] ?? [];

                for ($i = 0; $i < count($labels); $i++) {
                    if (!is_numeric($labels[$i])) {
                        $label = new Label();
                        $label->setShortName($labels[$i]);
                        $label->setReportName($labels[$i]);
                        $this->entityManager->persist($label);
                        $this->entityManager->flush();

                        $createdLabel = $this->labelRepository->findOneBy(['shortName' => $labels[$i]]);
                        $labels[$i] = $createdLabel->getId();
                    }
                }

                $data['labels'] = $labels;
                $event->setData($data);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'data_class' => Stub::class,
        ]);
    }
}
