<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Invoice;
use App\Form\Type\DateTimePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoicePaymentDateForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTimeOptions = [
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
        ];

        $builder
            ->add('paymentDate', DateTimePickerType::class, array_merge($dateTimeOptions, [
                'label' => 'label.paymentDate',
                'required' => true,
            ]));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
            'timezone' => date_default_timezone_get(),
            'time_increment' => 1,
            'attr' => [
                'data-form-event' => 'kimai.invoiceUpdate'
            ],
        ]);
    }
}
