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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Form\Type\ProjectType;

/**
 * Defines the form used to manipulate Activities.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityEditForm extends AbstractType
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
            // entity type: project
            ->add('project', ProjectType::class, [
                'label' => 'label.project',
            ])
            // boolean
            ->add('visible', VisibilityType::class, [
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
            'data_class' => Activity::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_activity_edit',
        ]);
    }
}
