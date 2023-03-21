<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\MultiUpdate;

use App\Form\Type\ActivityType;
use App\Form\Type\CustomerType;
use App\Form\Type\FixedRateType;
use App\Form\Type\HourlyRateType;
use App\Form\Type\MetaFieldsCollectionType;
use App\Form\Type\ProjectType;
use App\Form\Type\TagsType;
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use App\Repository\CustomerRepository;
use App\Repository\TimesheetRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TimesheetMultiUpdate extends AbstractType
{
    public function __construct(private TimesheetRepository $timesheet, private CustomerRepository $customers)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $activity = null;
        $project = null;
        $customer = null;
        $currency = null;
        $entry = null;
        $customerCount = $this->customers->countCustomer(true);

        if (isset($options['data'])) {
            /** @var TimesheetMultiUpdateDTO $entry */
            $entry = $options['data'];

            $activity = $entry->getActivity();
            $project = $entry->getProject();
            $customer = $project?->getCustomer();

            if (null === $project && null !== $activity) {
                $project = $activity->getProject();
            }

            if (null !== $customer) {
                $currency = $customer->getCurrency();
            }
        }

        $builder
            ->add('customer', CustomerType::class, [
                'query_builder_for_user' => true,
                'customers' => $customer,
                'data' => $customer,
                'required' => false,
                'placeholder' => '',
                'mapped' => false,
                'project_enabled' => true,
            ])
        ;

        $projectOptions = [];

        if ($customerCount < 2) {
            $projectOptions['group_by'] = null;
        }

        $builder->add('project', ProjectType::class, array_merge($projectOptions, [
            'required' => false,
            'placeholder' => '',
            'activity_enabled' => true,
            'customers' => $customer,
            'projects' => $project,
            'query_builder_for_user' => true,
        ]));

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        // TODO replace me with FormTrait
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($project, $customer) {
                $data = $event->getData();
                $customer = isset($data['customer']) && !empty($data['customer']) ? $data['customer'] : null;
                $project = isset($data['project']) && !empty($data['project']) ? $data['project'] : $project;

                $event->getForm()->add('project', ProjectType::class, [
                    'required' => false,
                    'placeholder' => '',
                    'activity_enabled' => true,
                    'group_by' => null,
                    'customers' => $customer,
                    'projects' => $project,
                    'query_builder_for_user' => true,
                ]);
            }
        );

        $activityOptions = [
            'required' => false,
            'placeholder' => '',
            'activities' => $activity,
            'query_builder_for_user' => true,
        ];

        $builder->add('activity', ActivityType::class, array_merge($activityOptions, [
            'projects' => $project,
        ]));

        // replaces the activity select after submission, to make sure only activities for the selected project are displayed
        // TODO replace me with FormTrait
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($activityOptions) {
                $data = $event->getData();
                if (!isset($data['project']) || empty($data['project'])) {
                    return;
                }

                $event->getForm()->add('activity', ActivityType::class, array_merge($activityOptions, [
                    'projects' => $data['project'],
                ]));
            }
        );

        $builder->add('replaceTags', ChoiceType::class, [
            'label' => false,
            'required' => true,
            'expanded' => true,
            'choices' => [
                'replaceTags' => true,
                'appendTags' => false,
            ]
        ]);

        $builder->add('tags', TagsType::class, [
            'required' => false,
        ]);

        if ($options['include_user']) {
            $builder->add('user', UserType::class, [
                'required' => false,
            ]);
        }

        if ($options['include_exported']) {
            $builder->add('exported', ChoiceType::class, [
                'label' => 'mark_as_exported',
                'required' => false,
                'choices' => [
                    'entryState.exported' => true,
                    'entryState.not_exported' => false
                ]
            ]);
        }

        if ($options['include_billable']) {
            $builder->add('billable', ChoiceType::class, [
                'label' => 'billable',
                'choices' => [
                    '' => null,
                    'yes' => true,
                    'no' => false,
                ],
            ]);
        }

        if ($options['include_rate']) {
            $builder
                ->add('recalculateRates', YesNoType::class, [
                    'label' => 'recalculate_rates',
                ])
                ->add('fixedRate', FixedRateType::class, [
                    'currency' => $currency,
                ])
                ->add('hourlyRate', HourlyRateType::class, [
                    'currency' => $currency,
                ])
            ;
        }

        // meta fields only if at least one exists
        if ($entry !== null && $entry->getMetaFields()->count() > 0) {
            $builder->add('metaFields', MetaFieldsCollectionType::class, ['fields_required' => false]);

            $choices = [];
            foreach ($entry->getMetaFields() as $field) {
                $name = $field->getName();
                $label = $entry->getMetaField($name)->getLabel();
                $choices[$label] = $name;
            }
            $builder->add('updateMeta', ChoiceType::class, [
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'label' => 'batch_meta_fields',
                'help' => 'help.batch_meta_fields',
            ]);
        }

        $builder->add('entities', HiddenType::class, [
            'required' => false,
        ]);

        $builder->get('entities')->addModelTransformer(
            new CallbackTransformer(
                function ($timesheets): string {
                    $ids = [];
                    /** @var \App\Entity\Timesheet $timesheet */
                    foreach ($timesheets as $timesheet) {
                        $ids[] = $timesheet->getId();
                    }

                    return implode(',', $ids);
                },
                function ($ids) {
                    if (empty($ids)) {
                        return [];
                    }

                    return $this->timesheet->matching((new Criteria())->where(Criteria::expr()->in('id', explode(',', $ids))));
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimesheetMultiUpdateDTO::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_multiupdate',
            'include_user' => false,
            'include_rate' => false,
            'include_exported' => false,
            'include_billable' => false,
        ]);
    }
}
