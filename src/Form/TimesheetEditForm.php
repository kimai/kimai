<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\DurationType;
use App\Form\Type\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Timesheet;
use App\Form\Type\ActivityGroupedWithCustomerNameType;
use App\Repository\ActivityRepository;

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

        $activity = null;
        if ($entry->getId() !== null) {
            $activity = $entry->getActivity();
        }

        if ($entry->getEnd() === null || !$options['duration_only']) {
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
            $builder->add('duration', DurationType::class, [
                'label_attr' => ['docu' => 'timesheet']
            ]);
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
            ->add('activity', ActivityGroupedWithCustomerNameType::class, [
                'label' => 'label.activity',
                'query_builder' => function (ActivityRepository $repo) use ($activity) {
                    return $repo->builderForEntityType($activity);
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
        ]);
    }
}
