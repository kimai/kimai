<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form;

use AppBundle\Form\Type\VisibilityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Form\Type\CustomerType;
use TimesheetBundle\Repository\CustomerRepository;

/**
 * Defines the form used to edit Projects.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectEditForm extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Project $entry */
        $entry = $options['data'];

        $customer = null;
        if ($entry->getId() !== null) {
            $customer = $entry->getCustomer();
        }

        $builder
            // string - length 255
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ])
            // text
            ->add('comment', TextareaType::class, [
                'label' => 'label.comment',
            ])
            // customer
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
                'query_builder' => function (CustomerRepository $repo) use ($customer) {
                    return $repo->builderForEntityType($customer);
                },
            ])
            // boolean
            ->add('visible', VisibilityType::class, [
                'label' => 'label.visible',
            ])
            // string
            ->add('budget', MoneyType::class, [
                'label' => 'label.budget',
                'currency' => $builder->getOption('currency'),
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_project_edit',
            'currency' => Customer::DEFAULT_CURRENCY,
        ]);
    }
}
