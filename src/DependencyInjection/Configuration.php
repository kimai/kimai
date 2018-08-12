<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class validates and merges configuration from the files:
 * - config/packages/kimai.yaml
 * - config/packages/local.yaml
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kimai');

        $rootNode
            ->children()
                ->append($this->getUserNode())
                ->append($this->getTimesheetNode())
                ->append($this->getInvoiceNode())
                ->append($this->getLanguagesNode())
                ->append($this->getCalendarNode())
                ->append($this->getThemeNode())
                ->append($this->getDashboardNode())
                ->append($this->getWidgetsNode())
            ->end()
        ->end();

        return $treeBuilder;
    }

    protected function getTimesheetNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('timesheet');

        $node
            ->children()
                ->booleanNode('duration_only')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('rounding')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('days')
                                ->requiresAtLeastOneElement()
                                ->useAttributeAsKey('key')
                                ->isRequired()
                                ->prototype('scalar')->end()
                                ->defaultValue([])
                            ->end()
                            ->integerNode('begin')
                                ->defaultValue(0)
                            ->end()
                            ->integerNode('end')
                                ->defaultValue(0)
                            ->end()
                            ->integerNode('duration')
                                ->defaultValue(0)
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()

                ->arrayNode('rates')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('days')
                                ->requiresAtLeastOneElement()
                                ->useAttributeAsKey('key')
                                ->isRequired()
                                ->prototype('scalar')->end()
                                ->defaultValue([])
                            ->end()
                            ->floatNode('factor')
                                ->isRequired()
                                ->defaultValue(1)
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getInvoiceNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('invoice');

        $node
            ->children()
                ->arrayNode('renderer')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->isRequired()
                    ->prototype('scalar')->end()
                    ->defaultValue([
                        'default' => 'App\Controller\InvoiceController::invoiceAction',
                    ])
                ->end()
                ->arrayNode('calculator')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->isRequired()
                    ->prototype('scalar')->end()
                    ->defaultValue([
                        'default' => 'App\Invoice\DefaultCalculator',
                    ])
                ->end()
                ->arrayNode('number_generator')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->isRequired()
                    ->prototype('scalar')->end()
                    ->defaultValue([
                        'default' => 'App\Invoice\DateNumberGenerator',
                    ])
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getLanguagesNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('languages');

        $node
            ->arrayPrototype()
                ->children()
                    ->scalarNode('date_short')->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getCalendarNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('calendar');

        $node
            ->children()
                ->booleanNode('week_numbers')->defaultTrue()->end()
                ->integerNode('day_limit')->defaultValue(4)->end()
                ->arrayNode('businessHours')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('days')
                            ->requiresAtLeastOneElement()
                            ->prototype('integer')->end()
                            ->defaultValue([1, 2, 3, 4, 5])
                        ->end()
                        ->scalarNode('begin')->defaultValue('08:00')->end()
                        ->scalarNode('end')->defaultValue('20:00')->end()
                    ->end()
                ->end()
                ->arrayNode('google')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('api_key')->defaultNull()->end()
                        ->arrayNode('sources')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('key')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('id')->isRequired()->end()
                                    ->scalarNode('color')->defaultValue('#ccc')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getThemeNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('theme');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('active_warning')
                    ->defaultValue(3)
                ->end()
                ->scalarNode('box_color')
                    ->defaultValue('green')
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getUserNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('user');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('registration')
                    ->defaultTrue()
                ->end()
                ->booleanNode('password_reset')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getWidgetsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('widgets');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('key')
            ->arrayPrototype()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('title')->isRequired()->end()
                    ->scalarNode('query')->isRequired()->end()
                    ->booleanNode('user')->defaultFalse()->end()
                    ->scalarNode('begin')->end()
                    ->scalarNode('end')->end()
                    ->scalarNode('icon')->isRequired()->end()
                    ->scalarNode('color')->defaultValue('')->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getDashboardNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('dashboard');

        $node
            ->requiresAtLeastOneElement()
                ->useAttributeAsKey('key')
                ->arrayPrototype()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('title')->end()
                        ->scalarNode('permission')->end()
                        ->arrayNode('widgets')
                            ->performNoDeepMerging()
                            ->scalarPrototype()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
