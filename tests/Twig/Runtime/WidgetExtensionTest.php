<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Entity\User;
use App\Twig\Runtime\WidgetExtension;
use App\Widget\Type\More;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

/**
 * @covers \App\Twig\Runtime\WidgetExtension
 */
class WidgetExtensionTest extends TestCase
{
    protected function getSut($hasWidget = null, $getWidget = null): WidgetExtension
    {
        $service = $this->createMock(WidgetService::class);
        if (null !== $hasWidget) {
            $service->expects($this->once())->method('hasWidget')->willReturn($hasWidget);
        }
        if (null !== $getWidget) {
            $service->expects($this->once())->method('getWidget')->willReturn($getWidget);
        }

        $interface = $this->createMock(TokenInterface::class);
        $interface->expects($this->any())->method('getUser')->willReturn(new User());
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->any())->method('getToken')->willReturn($interface);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('get')->willReturn($storage);

        $security = new Security($container);

        return new WidgetExtension($service, $security);
    }

    private function getEnvironment(): Environment
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->any())->method('render')->willReturnCallback(function (string $template, array $options) {
            self::assertArrayHasKey('data', $options);
            self::assertArrayHasKey('options', $options);
            self::assertArrayHasKey('title', $options);
            self::assertArrayHasKey('widget', $options);

            return json_encode($options['options']);
        });

        return $env;
    }

    public function testRenderWidgetForInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Widget must be either a WidgetInterface or a string');

        $sut = $this->getSut();
        /* @phpstan-ignore-next-line */
        $sut->renderWidget($this->getEnvironment(), true);
    }

    public function testRenderWidgetForUnknownWidget()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown widget "test" requested');

        $sut = $this->getSut(false);
        $sut->renderWidget($this->getEnvironment(), 'test');
    }

    public function testRenderWidgetByString()
    {
        $widget = new More();
        $widget->setId('test');
        $sut = $this->getSut(true, $widget);
        $options = ['foo' => 'bar', 'dataType' => 'blub'];
        $result = $sut->renderWidget($this->getEnvironment(), 'test', $options);
        $data = json_decode($result, true);
        $this->assertEquals($options, $data);
    }

    public function testRenderWidgetObject()
    {
        $widget = new More();
        $sut = $this->getSut(null, null);
        $options = ['foo' => 'bar', 'dataType' => 'blub'];
        $result = $sut->renderWidget($this->getEnvironment(), $widget, $options);
        $data = json_decode($result, true);
        $this->assertEquals($options, $data);
    }
}
