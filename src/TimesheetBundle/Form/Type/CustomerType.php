<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Repository\CustomerRepository;
use TimesheetBundle\Repository\Query\CustomerQuery;

/**
 * Custom form field type to select a customer.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.customer',
            'class' => 'TimesheetBundle:Customer',
            'choice_label' => 'name',
            'query_builder' => function (CustomerRepository $repo) {
                return $repo->builderForEntityType(null);
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
