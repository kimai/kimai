<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\Query\TimesheetQuery;

/**
 * Defines the form used for filtering the timesheet.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetToolbarForm extends AbstractToolbarForm
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addTimesheetStateChoice($builder);
        $this->addPageSizeChoice($builder);
        $this->addCustomerChoice($builder);
        $this->addProjectChoice($builder);
        $this->addActivityChoice($builder);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addTimesheetStateChoice(FormBuilderInterface $builder)
    {
        $builder->add('state', ChoiceType::class, [
            'label' => 'label.entryState',
            'choices' => [
                'entryState.all' => TimesheetQuery::STATE_ALL,
                'entryState.running' => TimesheetQuery::STATE_RUNNING,
                'entryState.stopped' => TimesheetQuery::STATE_STOPPED
            ],
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
