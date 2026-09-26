<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use App\Form\InvoiceEditForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

final class InvoiceApiEditForm extends AbstractType implements ApiFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('paymentDate', DateApiType::class, [
            'label' => 'invoice.payment_date',
            'required' => false,
            'description' => 'Date of the payment',
        ]);
    }

    public function getParent(): string
    {
        return InvoiceEditForm::class;
    }
}
