<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\ExportQuery;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        $this->addSearchTermInputField($builder);
        $this->addExportStateChoice($builder);
        $this->addTimesheetStateChoice($builder);
        $this->addUsersChoice($builder);
        $this->addDateRangeChoice($builder);
        $this->addCustomerMultiChoice($builder, ['start_date_param' => null, 'end_date_param' => null, 'ignore_date' => true], true);
        $this->addProjectMultiChoice($builder, ['ignore_date' => true], true, true);
        $this->addActivityMultiChoice($builder, [], true);
        $this->addExportType($builder);
        $this->addTagInputField($builder);
        $builder->add('markAsExported', CheckboxType::class, [
            'label' => 'label.mark_as_exported',
            'required' => false,
        ]);
        $builder->add('preview', SubmitType::class, [
            'label' => 'button.preview',
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addExportType(FormBuilderInterface $builder)
    {
        $builder->add('type', HiddenType::class, []);
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
