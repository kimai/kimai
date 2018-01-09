<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form\Toolbar;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Form\Type\ActivityType;
use TimesheetBundle\Repository\Query\TimesheetQuery;

/**
 * Defines the form used for filtering the timesheet.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetToolbarForm extends ActivityToolbarForm
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('state', ChoiceType::class, [
                'label' => 'label.entryState',
                'choices' => [
                    'entryState.all' => TimesheetQuery::STATE_ALL,
                    'entryState.running' => TimesheetQuery::STATE_RUNNING,
                    'entryState.stopped' => TimesheetQuery::STATE_STOPPED
                ],
            ])
        ;
        parent::buildForm($builder, $options);
        $this->addActivityChoice($builder, $options['data']);

        $builder->remove('visibility');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param TimesheetQuery $query
     */
    protected function addActivityChoice(FormBuilderInterface $builder, TimesheetQuery $query)
    {
        if ($query->getProject() === null) {
            return;
        }

        $choices = [];
        foreach ($query->getProject()->getActivities() as $activity) {
            $choices[] = $activity;
        }

        $builder
            ->add('activity', ActivityType::class, [
                'required' => false,
                'choices' => $choices,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimesheetQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
