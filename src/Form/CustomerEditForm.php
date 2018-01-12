<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\VisibilityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Customer;

/**
 * Defines the form used to edit Customer entities.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerEditForm extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // string - length 255
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            // text
            ->add('comment', TextareaType::class, [
                'label' => 'label.comment',
                'required' => false,
            ])
            // do not allow project selection:
            // 1. it is a bad UX
            // 2. what should happen if they are detached?
            /*
            ->add('projects', EntityType::class, [
                'label' => 'label.project',
                'class' => 'Kimai:Project',
                'multiple' => true,
                'expanded' => true
            ])
            */
            // boolean
            ->add('visible', VisibilityType::class, [
                'label' => 'label.visible',
            ])
            // string - length 255
            ->add('company', TextType::class, [
                'label' => 'label.company',
                'required' => false,
            ])
            // string - length 255
            ->add('vat', PercentType::class, [
                'label' => 'label.vat',
                'type' => 'integer',
            ])
            // string - length 255
            ->add('contact', TextType::class, [
                'label' => 'label.contact',
                'required' => false,
            ])
            // string - length 255
            ->add('address', TextareaType::class, [
                'label' => 'label.address',
                'required' => false,
            ])
            // string - length 2
            ->add('country', CountryType::class, [
                'label' => 'label.country',
            ])
            // string - length 3
            ->add('currency', CurrencyType::class, [
                'label' => 'label.currency',
            ])
            // string - length 255
            ->add('phone', TelType::class, [
                'label' => 'label.phone',
                'required' => false,
            ])
            // string - length 255
            ->add('fax', TelType::class, [
                'label' => 'label.fax',
                'required' => false,
            ])
            // string - length 255
            ->add('mobile', TelType::class, [
                'label' => 'label.mobile',
                'required' => false,
            ])
            // string - length 255
            ->add('mail', EmailType::class, [
                'label' => 'label.email',
                'required' => false,
            ])
            // string - length 255
            ->add('homepage', UrlType::class, [
                'label' => 'label.homepage',
                'required' => false,
            ])
            // string - length 255
            ->add('timezone', TimezoneType::class, [
                'label' => 'label.timezone',
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
