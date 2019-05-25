<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use App\Model\DashboardSection;
use App\Model\Widget;
use App\Timesheet\Rounding\RoundingInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $treeBuilder = new TreeBuilder('kimai');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('data_dir')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !file_exists($value);
                        })
                        ->thenInvalid('Data directory does not exist')
                    ->end()
                ->end()
                ->scalarNode('plugin_dir')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !file_exists($value);
                        })
                        ->thenInvalid('Plugin directory does not exist')
                    ->end()
                ->end()
                ->append($this->getUserNode())
                ->append($this->getTimesheetNode())
                ->append($this->getInvoiceNode())
                ->append($this->getLanguagesNode())
                ->append($this->getCalendarNode())
                ->append($this->getThemeNode())
                ->append($this->getDashboardNode())
                ->append($this->getWidgetsNode())
                ->append($this->getDefaultsNode())
                ->append($this->getPermissionsNode())
            ->end()
        ->end();

        return $treeBuilder;
    }

    protected function getTimesheetNode()
    {
        $builder = new TreeBuilder('timesheet');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->children()
                ->booleanNode('duration_only')
                    ->setDeprecated()
                ->end()
                ->scalarNode('mode')
                    ->defaultValue('default')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !in_array($value, ['default', 'duration_only']);
                        })
                        ->thenInvalid('Chosen timesheet mode is invalid, allowed values: default, duration_only')
                    ->end()
                ->end()
                ->booleanNode('markdown_content')
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
                            ->scalarNode('mode')
                                ->defaultValue('default')
                                ->validate()
                                    ->ifTrue(function ($value) {
                                        $class = 'App\\Timesheet\\Rounding\\' . ucfirst($value) . 'Rounding';
                                        if (class_exists($class)) {
                                            $rounding = new $class();

                                            return !($rounding instanceof RoundingInterface);
                                        }

                                        return false;
                                    })
                                    ->thenInvalid('Chosen rounding mode is invalid')
                                ->end()
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
                                ->validate()
                                    ->ifTrue(function ($value) {
                                        return $value <= 0;
                                    })
                                    ->thenInvalid('A rate factor smaller or equals 0 is not allowed')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('active_entries')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('soft_limit')
                            ->defaultValue(1)
                            ->validate()
                                ->ifTrue(function ($value) {
                                    return $value <= 0;
                                })
                                ->thenInvalid('The soft_limit must be at least 1')
                            ->end()
                        ->end()
                        ->integerNode('hard_limit')
                            ->defaultValue(1)
                            ->validate()
                                ->ifTrue(function ($value) {
                                    return $value <= 0;
                                })
                                ->thenInvalid('The hard_limit must be at least 1')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('rules')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('allow_future_times')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getInvoiceNode()
    {
        $builder = new TreeBuilder('invoice');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('documents')
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'var/invoices/',
                        'templates/invoice/renderer/'
                    ])
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getLanguagesNode()
    {
        $builder = new TreeBuilder('languages');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->useAttributeAsKey('name', false) // see https://github.com/symfony/symfony/issues/18988
            ->arrayPrototype()
                ->children()
                    ->scalarNode('date_time_type')->defaultValue('yyyy-MM-dd HH:mm')->end()     // for DateTimeType
                    ->scalarNode('date_type')->defaultValue('yyyy-MM-dd')->end()                // for DateType
                    ->scalarNode('date')->defaultValue('Y-m-d')->end()                          // for display via twig
                    ->scalarNode('date_time')->defaultValue('m-d H:i')->end()                   // for display via twig
                    ->scalarNode('duration')->defaultValue('%%h:%%m h')->end()                  // for display via twig
                    ->scalarNode('time')->defaultValue('H:i')->end()                            // for display via twig
                    ->booleanNode('24_hours')->defaultTrue()->end()                             // for DateTimeType JS component
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getCalendarNode()
    {
        $builder = new TreeBuilder('calendar');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
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
                ->arrayNode('visibleHours')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('begin')->defaultValue('00:00')->end()
                        ->scalarNode('end')->defaultValue('24:00')->end()
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
                ->booleanNode('weekends')->defaultTrue()->end()
            ->end()
        ;

        return $node;
    }

    protected function getThemeNode()
    {
        $builder = new TreeBuilder('theme');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('active_warning')
                    ->defaultValue(3)
                    ->setDeprecated('The node "%node%" at path "%path%" is deprecated, please use "kimai.timesheet.active_entries.soft_limit" instead.')
                ->end()
                ->scalarNode('box_color')
                    ->defaultValue('green')
                    ->setDeprecated('The node "%node%" at path "%path%" was removed, please delete it from your config.')
                ->end()
                ->scalarNode('select_type')
                    ->defaultNull()
                ->end()
                ->booleanNode('show_about')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getUserNode()
    {
        $builder = new TreeBuilder('user');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

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
        $builder = new TreeBuilder('widgets');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

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
                    ->scalarNode('icon')->defaultValue('')->end()
                    ->scalarNode('color')->defaultValue('')->end()
                    ->scalarNode('type')
                        ->validate()
                            ->ifNotInArray([Widget::TYPE_COUNTER, Widget::TYPE_MORE])->thenInvalid('Unknown widget type')
                        ->end()
                        ->defaultValue(Widget::TYPE_COUNTER)
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getDashboardNode()
    {
        $builder = new TreeBuilder('dashboard');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->requiresAtLeastOneElement()
                ->useAttributeAsKey('key')
                ->arrayPrototype()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->validate()
                                ->ifNotInArray([DashboardSection::TYPE_SIMPLE, DashboardSection::TYPE_CHART])->thenInvalid('Unknown section type')
                            ->end()
                            ->defaultValue(DashboardSection::TYPE_SIMPLE)
                        ->end()
                        ->integerNode('order')->defaultValue(0)->end()
                        ->scalarNode('title')->end()
                        ->scalarNode('permission')->isRequired()->end()
                        ->arrayNode('widgets')
                            ->isRequired()
                            ->performNoDeepMerging()
                            ->scalarPrototype()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getDefaultsNode()
    {
        $builder = new TreeBuilder('defaults');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('customer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('timezone')->defaultValue('Europe/Berlin')->end()
                        ->scalarNode('country')->defaultValue('DE')->end()
                        ->scalarNode('currency')->defaultValue('EUR')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getPermissionsNode()
    {
        $builder = new TreeBuilder('permissions');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('sets')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->performNoDeepMerging()
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->isRequired()
                        ->prototype('scalar')->end()
                        ->defaultValue([])
                    ->end()
                ->end()
                ->arrayNode('maps')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->performNoDeepMerging()
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->isRequired()
                        ->prototype('scalar')->end()
                        ->defaultValue([])
                    ->end()
                ->end()
                ->arrayNode('roles')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->performNoDeepMerging()
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->isRequired()
                        ->prototype('scalar')->end()
                        ->defaultValue([])
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
