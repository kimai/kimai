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
            'query_builder_for_user' => true,
            'project_enabled' => false,
            'project_select' => 'project',
            'start_date_param' => '%begin%',
            'end_date_param' => '%end%',
            'ignore_date' => false,
            'project_visibility' => ProjectQuery::SHOW_VISIBLE,
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (CustomerRepository $repo) use ($options) {
                $query = new CustomerFormTypeQuery();
                if (true === $options['query_builder_for_user']) {
                    $query->setUser($options['user']);
                }

                return $repo->getQueryBuilderForFormType($query);
            };
        });

        $resolver->setDefault('api_data', function (Options $options) {
            if (false !== $options['project_enabled']) {
                $name = \is_string($options['project_enabled']) ? $options['project_enabled'] : 'customer';
                $routeParams = [$name => '%' . $name . '%', 'visible' => $options['project_visibility']];
                $emptyRouteParams = ['visible' => $options['project_visibility']];

                if (!$options['ignore_date']) {
                    if (!empty($options['start_date_param'])) {
                        $routeParams['start'] = $options['start_date_param'];
                        $emptyRouteParams['start'] = $options['start_date_param'];
                    }

                    if (!empty($options['end_date_param'])) {
                        $routeParams['end'] = $options['end_date_param'];
                        $emptyRouteParams['end'] = $options['end_date_param'];
                    }
                } else {
                    $routeParams['ignoreDates'] = 1;
                    $emptyRouteParams['ignoreDates'] = 1;
                }

                return [
                    'select' => $options['project_select'],
                    'route' => 'get_projects',
                    'route_params' => $routeParams,
                    'empty_route_params' => $emptyRouteParams,
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
