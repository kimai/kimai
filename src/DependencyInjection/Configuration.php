<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
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
                        ->integerNode('rounding')->end()
                    ->end()
                ->end()
                ->arrayNode('invoice')
                    ->children()
                        ->arrayNode('renderer')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('key')
                            ->isRequired()
                            ->prototype('scalar')->end()
                            ->defaultValue(array(
                                'default' => 'App\Controller\InvoiceController::invoiceAction',
                            ))
                        ->end()
                        ->arrayNode('calculator')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('key')
                            ->isRequired()
                            ->prototype('scalar')->end()
                            ->defaultValue(array(
                                'default' => 'App\Invoice\DefaultCalculator',
                            ))
                        ->end()
                        ->arrayNode('number_generator')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('key')
                            ->isRequired()
                            ->prototype('scalar')->end()
                            ->defaultValue(array(
                                'default' => 'App\Invoice\DateNumberGenerator',
                            ))
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
