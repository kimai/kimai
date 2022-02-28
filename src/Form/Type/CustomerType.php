<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectQuery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a customer.
 */
class CustomerType extends AbstractType
{
    public const PATTERN_NAME = '{name}';
    public const PATTERN_NUMBER = '{number}';
    public const PATTERN_COMPANY = '{company}';
    public const PATTERN_COMMENT = '{comment}';
    public const PATTERN_SPACER = '{spacer}';
    public const SPACER = ' - ';

    private $configuration;
    private $pattern;

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    private function getPattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->configuration->find('customer.choice_pattern');

            if ($this->pattern === null || stripos($this->pattern, '{') === false || stripos($this->pattern, '}') === false) {
                $this->pattern = self::PATTERN_NAME;
            }

            $this->pattern = str_replace(self::PATTERN_SPACER, self::SPACER, $this->pattern);
        }

        return $this->pattern;
    }

    public function getChoiceLabel(Customer $customer): string
    {
        $name = $this->getPattern();
        $name = str_replace(self::PATTERN_NAME, $customer->getName(), $name);
        $name = str_replace(self::PATTERN_COMMENT, $customer->getComment() ?? '', $name);
        $name = str_replace(self::PATTERN_NUMBER, $customer->getNumber() ?? '', $name);
        $name = str_replace(self::PATTERN_COMPANY, $customer->getCompany() ?? '', $name);

        $name = ltrim($name, self::SPACER);
        $name = rtrim($name, self::SPACER);

        if ($name === '' || $name === self::SPACER) {
            $name = $customer->getName();
        }

        return substr($name, 0, 110);
    }

    public function getChoiceAttributes(Customer $customer, $key, $value): array
    {
        return ['data-currency' => $customer->getCurrency()];
    }

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
            'choice_label' => [$this, 'getChoiceLabel'],
            'choice_attr' => [$this, 'getChoiceAttributes'],
            'query_builder_for_user' => true,
            'project_enabled' => false,
            'project_select' => 'project',
            'start_date_param' => '%begin%',
            'end_date_param' => '%end%',
            'ignore_date' => false,
            'project_visibility' => ProjectQuery::SHOW_VISIBLE,
            // @var Customer|null
            'ignore_customer' => null,
            // @var Customer|Customer[]|null
            'customers' => null,
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (CustomerRepository $repo) use ($options) {
                $query = new CustomerFormTypeQuery($options['customers']);
                if (true === $options['query_builder_for_user']) {
                    $query->setUser($options['user']);
                }

                if (null !== $options['ignore_customer']) {
                    $query->setCustomerToIgnore($options['ignore_customer']);
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

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-option-pattern' => $this->getPattern(),
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
