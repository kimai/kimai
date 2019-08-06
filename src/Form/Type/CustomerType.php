<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectQuery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a customer.
 */
class CustomerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // documentation is for NelmioApiDocBundle
            'documentation' => [
                'type' => 'integer',
                'description' => 'Customer ID',
            ],
            'label' => 'label.customer',
            'class' => Customer::class,
            'choice_label' => 'name',
            'query_builder' => function (CustomerRepository $repo) use ($resolver) {
                $query = new CustomerFormTypeQuery();
                $query->setUser($resolver->offsetGet('user'));

                return $repo->getQueryBuilderForFormType($query);
            },
            'project_enabled' => false,
            'project_visibility' => ProjectQuery::SHOW_VISIBLE,
        ]);

        $resolver->setDefault('api_data', function (Options $options) {
            if (true === $options['project_enabled']) {
                return [
                    'select' => 'project',
                    'route' => 'get_projects',
                    'route_params' => ['customer' => '-s-', 'visible' => $options['project_visibility']],
                    'empty_route_params' => ['visible' => $options['project_visibility']],
                ];
            }

            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
