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
use App\Form\Type\LanguageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to manipulate invoice templates.
 */
final class InvoiceTemplateForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
            ])
            ->add('title', TextType::class, [
                'label' => 'title',
            ])
            ->add('company', TextType::class, [
                'label' => 'company',
            ])
            ->add('vatId', TextType::class, [
                'label' => 'vat_id',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'address',
                'required' => false,
            ])
            ->add('contact', TextareaType::class, [
                'label' => 'contact',
                'required' => false,
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label' => 'payment_terms',
                'required' => false,
            ])
            ->add('paymentDetails', TextareaType::class, [
                'label' => 'invoice_bank_account',
                'required' => false,
            ])
            ->add('dueDays', IntegerType::class, [
                'label' => 'due_days',
            ])
            ->add('vat', NumberType::class, [
                'label' => 'tax_rate',
                'scale' => 3,
            ])
            ->add('renderer', InvoiceRendererType::class)
            ->add('calculator', InvoiceCalculatorType::class)
            ->add('numberGenerator', InvoiceNumberGeneratorType::class)
            ->add('language', LanguageType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
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
            'docu_chapter' => 'invoices.html',
        ]);
    }
}
