<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Customer;
use App\Form\Type\InvoiceTemplateType;
use App\Form\Type\MailType;
use App\Form\Type\TeamType;
use App\Form\Type\TimezoneType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Customer>
 */
class CustomerEditForm extends AbstractType
{
    use EntityFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = false;

        if (isset($options['data'])) {
            /** @var Customer $customer */
            $customer = $options['data'];
            $isNew = $customer->getId() === null;
            $options['currency'] = $customer->getCurrency();
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
                'attr' => [
                    'autofocus' => 'autofocus'
                ],
            ])
            ->add('number', TextType::class, [
                'label' => 'number',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'description',
                'required' => false,
            ])
            ->add('company', TextType::class, [
                'label' => 'company',
                'required' => false,
            ])
            ->add('vatId', TextType::class, [
                'label' => 'vat_id',
                'required' => false,
            ])
            ->add('contact', TextType::class, [
                'label' => 'contact',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'address',
                'required' => false,
            ])
            ->add('country', CountryType::class, [
                'label' => 'country',
            ])
            ->add('currency', CurrencyType::class, [
                'label' => 'currency',
            ])
            ->add('phone', TelType::class, [
                'label' => 'phone',
                'required' => false,
                'block_prefix' => 'phone',
            ])
            ->add('fax', TelType::class, [
                'label' => 'fax',
                'required' => false,
                'attr' => ['icon' => 'fax'],
                'block_prefix' => 'phone',
            ])
            ->add('mobile', TelType::class, [
                'label' => 'mobile',
                'required' => false,
                'attr' => ['icon' => 'mobile'],
                'block_prefix' => 'phone',
            ])
            ->add('email', MailType::class, [
                'required' => false,
            ])
            ->add('homepage', UrlType::class, [
                'label' => 'homepage',
                'required' => false,
                'block_prefix' => 'homepage',
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'timezone',
            ])
            ->add('invoiceText', TextareaType::class, [
                'label' => 'invoiceText',
                'help' => 'help.invoiceText',
                'required' => false,
            ])
            ->add('invoiceTemplate', InvoiceTemplateType::class, [
                'help' => 'help.invoiceTemplate_customer',
                'required' => false,
            ])
        ;

        if ($isNew) {
            $builder
                ->add('teams', TeamType::class, [
                    'required' => false,
                    'multiple' => true,
                    'expanded' => false,
                    'by_reference' => false,
                    'help' => 'help.teams',
                ]);
        }

        $this->addCommonFields($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_customer_edit',
            'currency' => Customer::DEFAULT_CURRENCY,
            'include_budget' => false,
            'include_time' => false,
            'attr' => [
                'data-form-event' => 'kimai.customerUpdate'
            ],
        ]);
    }
}
