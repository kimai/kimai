<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\InvoiceTemplateType;
use App\Repository\Query\InvoiceQuery;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for invoices.
 */
class InvoiceToolbarForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addSearchTermInputField($builder);
        $this->addTemplateChoice($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
        }
        $this->addDateRangeChoice($builder);
        $this->addCustomerChoice($builder, true);
        $this->addProjectChoice($builder);
        $this->addActivityChoice($builder);
        $this->addTagInputField($builder);
        $this->addExportStateChoice($builder);
        $builder->add('markAsExported', CheckboxType::class, [
            'label' => 'label.mark_as_exported',
            'required' => false,
        ]);
        $builder->add('create', SubmitType::class, [
            'label' => 'button.print',
            'attr' => ['formtarget' => '_blank'],
        ]);
        $builder->add('preview', SubmitType::class, [
            'label' => 'button.preview',
        ]);
    }

    protected function addTemplateChoice(FormBuilderInterface $builder)
    {
        $builder->add('template', InvoiceTemplateType::class, [
            'required' => true,
            'placeholder' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoiceQuery::class,
            'csrf_protection' => false,
            'include_user' => true,
        ]);
    }
}
