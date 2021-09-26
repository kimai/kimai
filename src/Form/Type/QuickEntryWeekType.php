<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\FormTrait;
use App\Model\QuickEntryModel;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class QuickEntryWeekType extends AbstractType
{
    use FormTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isNew = true; // each row can contain new entries
        $customer = null;
        $project = null;
        $activity = null;

        $projectOptions = ['label' => false, 'required' => false];
        $this->addProject($builder, $isNew, $project, $customer, $projectOptions);

        $activityOptions = ['label' => false, 'required' => false];
        $this->addActivity($builder, $activity, $project, $activityOptions);

        $activityFullOptions = $this->buildActivityOptions($activityOptions);
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($builder, $activityFullOptions) {
                /** @var QuickEntryModel $data */
                $data = $event->getData();
                if ($data === null) {
                    return;
                }

                $event->getForm()->add('activity', ActivityType::class, array_merge($activityFullOptions, [
                    'query_builder' => function (ActivityRepository $repo) use ($builder, $data) {
                        $query = new ActivityFormTypeQuery($data->getActivity(), $data->getProject());
                        $query->setUser($builder->getOption('user'));

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]));
            }
        );

        $builder->add('timesheets', CollectionType::class, [
            'entry_type' => QuickEntryTimesheetType::class,
            'label' => false,
            'entry_options' => [
                'label' => false,
                'compound' => true,
                'timezone' => $options['timezone'],
                'duration_minutes' => $options['duration_minutes'],
                'duration_hours' => $options['duration_hours'],
            ],
            'constraints' => [
                new Valid()
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => QuickEntryModel::class,
            'timezone' => date_default_timezone_get(),
            'duration_minutes' => null,
            'duration_hours' => 10,
        ]);
    }
}
