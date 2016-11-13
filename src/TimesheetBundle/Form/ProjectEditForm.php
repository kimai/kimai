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

use AppBundle\Form\Type\YesNoType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Form\Type\CustomerType;

/**
 * Defines the form used to manipulate Projects.
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
        $builder
            // string - length 255
            ->add('name', null, [
                'label' => 'label.name',
            ])
            // text
            ->add('comment', TextareaType::class, [
                'label' => 'label.comment',
            ])
            // customer
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
            ])
            // boolean
            ->add('visible', YesNoType::class, [
                'label' => 'label.visible',
            ])
            // string
            ->add('budget', MoneyType::class, [
                'label' => 'label.budget',
                'currency' => $builder->getOption('currency'),
            ])
            // FIXME add budget
            // do not allow activity selection as this causes headaches:
            // 1. it is a bad UX
            // 2. what should happen if they are detached?
            /*
            ->add('activities', EntityType::class, [
                'label' => 'label.activity',
                'class' => 'TimesheetBundle:Activity',
                'multiple' => true,
                'expanded' => true
            ])
            */
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
            'csrf_token_id' => 'admin_activity_edit',
            'currency' => 'EUR,'
        ]);
    }
}
