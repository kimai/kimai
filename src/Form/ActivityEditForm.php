<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Activity;
use App\Form\Type\CustomerType;
use App\Form\Type\ProjectType;
use App\Form\Type\YesNoType;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to manipulate Activities.
 */
class ActivityEditForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Activity $entry */
        $entry = $options['data'];

        $project = null;
        $customer = null;

        if (null !== $entry->getProject()) {
            $project = $entry->getProject();
            $customer = $project->getCustomer();
        }

        $builder
            // string - length 255
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            // text
            ->add('comment', TextareaType::class, [
                'label' => 'label.comment',
                'required' => false,
            ])
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
                'query_builder' => function (CustomerRepository $repo) use ($customer) {
                    return $repo->builderForEntityType($customer);
                },
                'data' => $customer ? $customer : null,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'data-related-select' => $this->getBlockPrefix() . '_project',
                    'data-api-url' => ['get_projects', ['customer' => '-s-']],
                ],
            ])
            ->add('project', ProjectType::class, [
                'label' => 'label.project',
                'required' => false,
                'query_builder' => function (ProjectRepository $repo) use ($project) {
                    return $repo->builderForEntityType($project);
                },
            ])
            ->add('fixedRate', NumberType::class, [
                'label' => 'label.fixed_rate',
                'required' => false,
            ])
            ->add('hourlyRate', NumberType::class, [
                'label' => 'label.hourly_rate',
                'required' => false,
            ])
            // boolean
            ->add('visible', YesNoType::class, [
                'label' => 'label.visible',
            ])
        ;

        if ($entry->getId() === null) {
            $builder->add('create_more', CheckboxType::class, [
                'label' => 'label.create_more',
                'required' => false,
                'mapped' => false,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_activity_edit',
        ]);
    }
}
