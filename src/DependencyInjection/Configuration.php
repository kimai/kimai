<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use App\Entity\Customer;
use App\Entity\User;
use App\Timesheet\Rounding\RoundingInterface;
use App\Widget\Type\CompoundRow;
use App\Widget\Type\Counter;
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
                ->append($this->getIndustryNode())
                ->append($this->getDashboardNode())
                ->append($this->getWidgetsNode())
                ->append($this->getDefaultsNode())
                ->append($this->getPermissionsNode())
                ->append($this->getLdapNode())
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
                ->scalarNode('default_begin')
                    ->defaultValue('now')
                ->end()
                ->booleanNode('duration_only')
                    ->setDeprecated()
                ->end()
                ->scalarNode('mode')
                    ->defaultValue('default')
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
                                ->scalarPrototype()->end()
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
                                ->scalarPrototype()->end()
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
                ->scalarNode('slot_duration')->defaultValue('00:30:00')->end()
                ->arrayNode('businessHours')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('days')
                            ->requiresAtLeastOneElement()
                            ->integerPrototype()->end()
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
                        ->scalarNode('end')->defaultValue('23:59')->end()
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
                    ->defaultValue('selectpicker')
                ->end()
                ->scalarNode('auto_reload_datatable')
                    ->defaultFalse()
                ->end()
                ->booleanNode('show_about')
                    ->defaultTrue()
                ->end()
                ->arrayNode('chart')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('background_color')->defaultValue('rgba(0,115,183,0.7)')->end()
                        ->scalarNode('border_color')->defaultValue('#3b8bba')->end()
                        ->scalarNode('grid_color')->defaultValue('rgba(0,0,0,.05)')->end()
                        ->scalarNode('height')->defaultValue('200')->end()
                    ->end()
                ->end()
                ->arrayNode('branding')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('logo')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('mini')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('company')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('title')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('translation')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->integerNode('autocomplete_chars')
                    ->defaultValue(3)
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getIndustryNode()
    {
        $builder = new TreeBuilder('industry');
        /** @var ArrayNodeDefinition $rootNode */
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('translation')->defaultNull()->end()
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
                    ->scalarNode('type')->defaultValue(Counter::class)->end()
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
                        ->scalarNode('type')->defaultValue(CompoundRow::class)->end()
                        ->integerNode('order')->defaultValue(0)->end()
                        ->scalarNode('title')->end()
                        ->scalarNode('permission')->defaultNull()->end()
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
                        ->scalarNode('timezone')->defaultNull()->end()
                        ->scalarNode('country')->defaultValue('DE')->end()
                        ->scalarNode('currency')->defaultValue(Customer::DEFAULT_CURRENCY)->end()
                    ->end()
                ->end()
                ->arrayNode('user')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('timezone')->defaultNull()->end()
                        ->scalarNode('language')->defaultValue(User::DEFAULT_LANGUAGE)->end()
                        ->scalarNode('theme')->defaultNull()->end()
                        ->scalarNode('currency')->defaultValue(Customer::DEFAULT_CURRENCY)->end()
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
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->isRequired()
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()
                ->end()
                ->arrayNode('maps')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->isRequired()
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()
                ->end()
                ->arrayNode('roles')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->isRequired()
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                    ->end()
                    ->defaultValue([
                        'ROLE_USER' => [],
                        'ROLE_TEAMLEAD' => [],
                        'ROLE_ADMIN' => [],
                        'ROLE_SUPER_ADMIN' => [],
                    ])
                ->end()
            ->end()
        ;

        return $node;
    }

    protected function getLdapNode()
    {
        $treeBuilder = new TreeBuilder('ldap');
        $node = $treeBuilder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('connection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')->defaultNull()->end()
                        ->scalarNode('port')->defaultValue(389)->end()
                        ->scalarNode('useStartTls')->defaultFalse()->end()
                        ->scalarNode('useSsl')->defaultFalse()->end()
                        ->scalarNode('username')->end()
                        ->scalarNode('password')->end()
                        ->scalarNode('bindRequiresDn')->defaultTrue()->end()
                        ->scalarNode('baseDn')->end()
                        ->scalarNode('accountCanonicalForm')->end()
                        ->scalarNode('accountDomainName')->end()
                        ->scalarNode('accountDomainNameShort')->end()
                        ->scalarNode('accountFilterFormat')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(static function ($v) {
                                    if (empty($v)) {
                                        return false;
                                    }
                                    if ($v[0] !== '(' || (substr_count($v, '(') !== substr_count($v, ')'))) {
                                        return true;
                                    }

                                    return (substr_count($v, '%s') !== 1);
                                })
                                ->thenInvalid('The accountFilterFormat must be enclosed by a matching number of parentheses "()" and contain one "%%s" replacer for the username')
                            ->end()
                        ->end()
                        ->scalarNode('allowEmptyPassword')->end()
                        ->scalarNode('optReferrals')->end()
                        ->scalarNode('tryUsernameSplit')->end()
                        ->scalarNode('networkTimeout')->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static function ($v) {
                            return $v['useSsl'] && $v['useStartTls'];
                        })
                        ->thenInvalid('The ldap.connection.useSsl and ldap.connection.useStartTls options are mutually exclusive.')
                    ->end()
                ->end()
                ->arrayNode('user')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('baseDn')->defaultNull()->end()
                        ->scalarNode('filter')
                            ->defaultValue('')
                            ->validate()
                                ->ifTrue(static function ($v) {
                                    if (empty($v)) {
                                        return false;
                                    }
                                    if ($v[0] !== '(' || (substr_count($v, '(') !== substr_count($v, ')'))) {
                                        return true;
                                    }

                                    return (stripos($v, '%s') !== false);
                                })
                                ->thenInvalid('The ldap.user.filter must be enclosed by a matching number of parentheses "()" and must NOT contain a "%%s" replacer')
                            ->end()
                        ->end()
                        ->scalarNode('attributesFilter')->defaultValue('(objectClass=*)')->end()
                        ->scalarNode('usernameAttribute')->defaultValue('uid')->end()
                        ->arrayNode('attributes')
                            ->defaultValue([])
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('ldap_attr')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('user_method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('role')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('baseDn')->defaultNull()->end()
                        ->scalarNode('filter')->end()
                        ->scalarNode('usernameAttribute')->defaultValue('dn')->end()
                        ->scalarNode('nameAttribute')->defaultValue('cn')->end()
                        ->scalarNode('userDnAttribute')->defaultValue('member')->end()
                        ->arrayNode('groups')
                            ->defaultValue([])
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('ldap_value')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('role')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static function ($v) {
                    return null !== $v['connection']['host'] && !extension_loaded('ldap');
                })
                ->thenInvalid('LDAP is activated, but the LDAP PHP extension is not loaded.')
            ->end()
            ->validate()
                ->ifTrue(static function ($v) {
                    return null !== $v['connection']['host'] && empty($v['user']['baseDn']);
                })
                ->thenInvalid('The "ldap.user.baseDn" config must be set if LDAP is activated.')
            ->end()
        ;

        return $node;
    }
}
