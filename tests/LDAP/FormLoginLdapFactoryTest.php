<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Ldap\FormLoginLdapFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \App\Ldap\FormLoginLdapFactory
 */
class FormLoginLdapFactoryTest extends TestCase
{
    public function testStaticValues()
    {
        $sut = new FormLoginLdapFactory();
        self::assertEquals('kimai_ldap', $sut->getKey());
        self::assertEquals('pre_auth', $sut->getPosition());
    }

    public function testCreate()
    {
        $container = new ContainerBuilder();
        $sut = new FormLoginLdapFactory();
        $result = $sut->create($container, 'test', ['foo' => 'bar'], 'fosuserbundle', 'secured_area');

        self::assertEquals([
            'kimai_ldap.security.authentication.provider.test',
            'security.authentication.listener.form.test',
            'secured_area'
        ], $result);

        $definition = $container->getDefinition('kimai_ldap.security.authentication.provider.test');
        self::assertInstanceOf(ChildDefinition::class, $definition);
        self::assertEquals('test', $definition->getArguments()['index_1']);

        $definition = $container->getDefinition('security.authentication.listener.form.test');
        self::assertInstanceOf(ChildDefinition::class, $definition);
        self::assertEquals('test', $definition->getArguments()['index_4']);
        self::assertEquals(['foo' => 'bar'], $definition->getArguments()['index_5']);
    }
}
