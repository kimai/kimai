<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form\Toolbar;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Form\Type\ProjectType;
use TimesheetBundle\Repository\Query\ActivityQuery;

/**
 * Defines the form used for filtering the activities.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityToolbarForm extends ProjectToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /** @var ActivityQuery $query */
        $query = $options['data'];

        if ($query->getCustomer() === null) {
            return;
        }

        $choices = [];
        foreach ($query->getCustomer()->getProjects() as $project) {
            $choices[] = $project;
        }

        $builder
            ->add('project', ProjectType::class, [
                'required' => false,
                'choices' => $choices,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
