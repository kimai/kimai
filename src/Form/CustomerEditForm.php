<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Customer;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to edit Customer entities.
 */
class CustomerEditForm extends AbstractType
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
            ->add('number', TextType::class, [
                'label' => 'label.customer_number',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'label.comment',
                'required' => false,
            ])
            ->add('company', TextType::class, [
                'label' => 'label.company',
                'required' => false,
            ])
            ->add('contact', TextType::class, [
                'label' => 'label.contact',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'label.address',
                'required' => false,
            ])
            ->add('country', CountryType::class, [
                'label' => 'label.country',
            ])
            ->add('currency', CurrencyType::class, [
                'label' => 'label.currency',
            ])
            ->add('phone', TelType::class, [
                'label' => 'label.phone',
                'required' => false,
            ])
            ->add('fax', TelType::class, [
                'label' => 'label.fax',
                'required' => false,
            ])
            ->add('mobile', TelType::class, [
                'label' => 'label.mobile',
                'required' => false,
            ])
            ->add('mail', EmailType::class, [
                'label' => 'label.email',
                'required' => false,
            ])
            ->add('homepage', UrlType::class, [
                'label' => 'label.homepage',
                'required' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'label.timezone',
            ])
            ->add('fixedRate', NumberType::class, [
                'label' => 'label.fixed_rate',
                'required' => false,
            ])
            ->add('hourlyRate', NumberType::class, [
                'label' => 'label.hourly_rate',
                'required' => false,
            ])
            ->add('visible', YesNoType::class, [
                'label' => 'label.visible',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_customer_edit',
        ]);
    }
}
