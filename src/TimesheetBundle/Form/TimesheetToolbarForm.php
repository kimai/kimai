<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Form\Type\ActivityType;
use TimesheetBundle\Form\Type\CustomerType;
use TimesheetBundle\Form\Type\ProjectType;
use TimesheetBundle\Model\Query\TimesheetQuery;

/**
 * Defines the form used for filtering the timesheet.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetToolbarForm extends AbstractType
{
    /**
     * Dirty hack to enable easy handling of GET form in controller and javascript.
     *Cleans up the name of all form elents (and unfortunately of the form itself).
     *
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TimesheetQuery $query */
        $query = $options['data'];

        $builder
            ->add('pageSize', ChoiceType::class, [
                'label' => 'label.pageSize',
                'choices' => [10 => 10, 25 => 25, 50 => 50, 75 => 75, 100 => 100],
                'required' => false,
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'label.entryState',
                'choices' => [
                    'entryState.all' => TimesheetQuery::STATE_ALL,
                    'entryState.running' => TimesheetQuery::STATE_RUNNING,
                    'entryState.stopped' => TimesheetQuery::STATE_STOPPED
                ],
            ])
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
                'required' => false,
            ])
        ;

        $this->addProjectChoice($builder, $query);
        $this->addActivityChoice($builder, $query);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param TimesheetQuery $query
     */
    protected function addProjectChoice(FormBuilderInterface $builder, TimesheetQuery $query)
    {
        if ($query->getCustomer() === null) {
            return;
        }

        $choices = [];
        foreach ($query->getCustomer()->getProjects() as $project) {
            $choices[] = $project;
        }

        $builder
            ->add('project', ProjectType::class, [
                'label' => 'label.project',
                'required' => false,
                'choices' => $choices,
            ]);
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
            //$choices[$activity->getName()] = $activity->getId();
        }

        $builder
            ->add('activity', ActivityType::class, [
                'label' => 'label.activity',
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
