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
class InvoiceToolbarSimpleForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addTemplateChoice($builder);
        $this->addDateRangeChoice($builder);
        $this->addCustomerChoice($builder, ['required' => true, 'start_date_param' => null, 'end_date_param' => null, 'ignore_date' => true, 'placeholder' => ''], true);
        $this->addProjectMultiChoice($builder, ['ignore_date' => true], false, true);
        $builder->add('markAsExported', CheckboxType::class, [
            'label' => 'label.mark_as_exported',
            'required' => false,
        ]);
        $builder->add('create', SubmitType::class, [
            'label' => 'action.save',
        ]);
        $builder->add('print', SubmitType::class, [
            'label' => 'button.preview_print',
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
