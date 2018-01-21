<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\Query\ActivityQuery;

/**
 * Defines the form used for filtering the activities.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityToolbarForm extends AbstractToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addPageSizeChoice($builder);
        $this->addVisibilityChoice($builder);
        $this->addCustomerChoice($builder);
        $this->addProjectChoice($builder);
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
