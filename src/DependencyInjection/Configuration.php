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
 * This is the class that validates and merges configuration from your app/config files
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
            ->end()
        ->end();

        return $treeBuilder;
    }
}
