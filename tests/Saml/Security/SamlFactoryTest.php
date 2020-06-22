<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Security;

use App\Saml\Security\SamlFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \App\Saml\Security\SamlFactory
 */
class SamlFactoryTest extends TestCase
{
    public function testStaticValues()
    {
        $sut = new SamlFactory();
        self::assertEquals('kimai_saml', $sut->getKey());
        self::assertEquals('pre_auth', $sut->getPosition());
    }

    public function testCreate()
    {
        $container = new ContainerBuilder();
        $sut = new SamlFactory();
        $result = $sut->create($container, 'test', ['foo' => 'bar', 'login_path' => null, 'use_forward' => null], 'fosuserbundle', 'secured_area');

        self::assertEquals([
            'security.authentication.provider.saml.test',
            'kimai.saml_listener.test',
            'secured_area'
        ], $result);

        $definition = $container->getDefinition('security.authentication.provider.saml.test');
        self::assertInstanceOf(ChildDefinition::class, $definition);
        self::assertCount(1, $definition->getArguments());
    }
}
