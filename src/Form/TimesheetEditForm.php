<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Timesheet;
use App\Form\Type\ActivityGroupedWithCustomerNameType;
use App\Form\Type\CustomerType;
use App\Form\Type\DurationType;
use App\Form\Type\ProjectType;
use App\Form\Type\UserType;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to manipulate Timesheet entries.
 */
class TimesheetEditForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Timesheet $entry */
        $entry = $options['data'];

        $activity = $entry->getActivity();
        $project = $entry->getProject();

        if (null === $project && null !== $activity) {
            $project = $activity->getProject();
        }

        if (null === $entry->getEnd() || !$options['duration_only']) {
            $builder->add('begin', DateTimeType::class, [
                'label' => 'label.begin',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd H:m',
                'with_seconds' => false,
                'attr' => ['data-datetimepicker' => 'on'],
            ]);
        }

        if ($options['duration_only']) {
            $builder->add('duration', DurationType::class);
        } else {
            $builder->add('end', DateTimeType::class, [
                'label' => 'label.end',
                'widget' => 'single_text',
                'required' => false,
                'html5' => false,
                'format' => 'yyyy-MM-dd H:m',
                'with_seconds' => false,
                'attr' => ['data-datetimepicker' => 'on'],
            ]);
        }

        $builder
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
                'query_builder' => function (CustomerRepository $repo) {
                    return $repo->builderForEntityType();
                },
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'data-related-select' => $this->getBlockPrefix() . '_project',
                    'data-api-url' => 'get_projects',
                ],
            ])
            ->add('project', ProjectType::class, [
                'required' => false,
                'label' => 'label.project',
                'query_builder' => function (ProjectRepository $repo) use ($project) {
                    return $repo->builderForEntityType($project);
                },
                'attr' => [
                    'data-related-select' => $this->getBlockPrefix() . '_activity',
                    'data-api-url' => 'get_activities',
                ],
            ])
            ->add('activity', ActivityGroupedWithCustomerNameType::class, [
                'label' => 'label.activity',
                'query_builder' => function (ActivityRepository $repo) use ($activity, $project) {
                    return $repo->builderForEntityType($activity, $project);
                },
            ])
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
            ])
        ;

        if ($options['include_user']) {
            $builder->add('user', UserType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Timesheet::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_edit',
            'duration_only' => false,
            'include_user' => false,
            'docu_chapter' => 'timesheet',
        ]);
    }
}
