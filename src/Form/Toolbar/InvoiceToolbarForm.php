<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering timesheet entries for invoices.
 */
class InvoiceToolbarForm extends InvoiceToolbarSimpleForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $this->addSearchTermInputField($builder);
        if ($options['include_user']) {
            $this->addUsersChoice($builder);
        }
        $this->addActivityMultiChoice($builder, $options, true);
        $this->addTagInputField($builder);
        $this->addExportStateChoice($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('include_user', true);
    }
}
