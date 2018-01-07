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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Form\Type\CustomerType;
use TimesheetBundle\Repository\Query\ProjectQuery;

/**
 * Defines the form used for filtering the projects.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectToolbarForm extends CustomerToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ProjectQuery $query */
        $query = $options['data'];

        parent::buildForm($builder, $options);
        $builder
            ->add('customer', CustomerType::class, [
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProjectQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
