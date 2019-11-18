<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\InvoiceTemplate;
use App\Form\Type\InvoiceCalculatorType;
use App\Form\Type\InvoiceNumberGeneratorType;
use App\Form\Type\InvoiceRendererType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to manipulate invoice templates.
 */
class InvoiceTemplateForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('company', TextType::class, [
                'label' => 'label.company',
            ])
            ->add('vatId', TextType::class, [
                'label' => 'label.vat_id',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'label.address',
                'required' => false,
            ])
            ->add('contact', TextareaType::class, [
                'label' => 'label.contact',
                'required' => false,
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label' => 'label.payment_terms',
                'required' => false,
            ])
            ->add('paymentDetails', TextareaType::class, [
                'label' => 'label.invoice_bank_account',
                'required' => false,
            ])
            ->add('dueDays', TextType::class, [
                'label' => 'label.due_days',
            ])
            ->add('vat', NumberType::class, [
                'label' => 'label.vat',
                'scale' => 2,
            ])
            ->add('renderer', InvoiceRendererType::class, [])
            ->add('calculator', InvoiceCalculatorType::class, [])
            ->add('numberGenerator', InvoiceNumberGeneratorType::class, [])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoiceTemplate::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_invoice_template',
            'attr' => [
                'data-form-event' => 'kimai.invoiceTemplateUpdate',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);
    }
}
