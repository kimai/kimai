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
                ->arrayNode('theme')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('active_warning')
                            ->defaultValue(3)
                        ->end()
                        ->scalarNode('box_color')
                            ->defaultValue('green')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('user')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('registration')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('password_reset')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('timesheet')
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
                ->end()
                ->arrayNode('invoice')
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
                ->end()
                ->arrayNode('languages')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('date_short')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('calendar')
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
                 ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
