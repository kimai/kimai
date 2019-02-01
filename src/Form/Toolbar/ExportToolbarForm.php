<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\ExportQuery;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for exports.
 */
class ExportToolbarForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addExportStateChoice($builder);
        $this->addUserChoice($builder);
        $this->addStartDateChoice($builder);
        $this->addEndDateChoice($builder);
        $this->addCustomerChoice($builder);
        $this->addProjectChoice($builder);
        $this->addActivityChoice($builder);
        $this->addExportType($builder);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addExportType(FormBuilderInterface $builder)
    {
        $builder->add('type', HiddenType::class, []);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addExportStateChoice(FormBuilderInterface $builder)
    {
        $builder->add('exported', ChoiceType::class, [
            'label' => 'label.exported',
            'required' => false,
            'placeholder' => null,
            'choices' => [
                'entryState.all' => TimesheetQuery::STATE_ALL,
                'entryState.exported' => TimesheetQuery::STATE_EXPORTED,
                'entryState.not_exported' => TimesheetQuery::STATE_NOT_EXPORTED
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExportQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
