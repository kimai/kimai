<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Makes sure that every core controller route declares its access rules with #[IsGranted],
 * instead of only relying on the firewall-level access_control.
 */
#[Group('integration')]
class ControllerIsGrantedTest extends KernelTestCase
{
    /**
     * Namespaces which intentionally contain public routes (login, registration, password reset, SAML).
     */
    private const PUBLIC_NAMESPACES = [
        'App\\Controller\\Auth\\',
        'App\\Controller\\Security\\',
    ];

    public function testAllCoreRoutesHaveIsGranted(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');

        $checked = 0;
        $missing = [];

        foreach ($router->getRouteCollection()->all() as $name => $route) {
            $controller = $route->getDefault('_controller');
            if (!\is_string($controller) || !str_starts_with($controller, 'App\\')) {
                continue;
            }

            foreach (self::PUBLIC_NAMESPACES as $namespace) {
                if (str_starts_with($controller, $namespace)) {
                    continue 2;
                }
            }

            [$class, $method] = str_contains($controller, '::') ? explode('::', $controller, 2) : [$controller, '__invoke'];
            self::assertTrue(class_exists($class), 'Unknown controller class: ' . $class);

            $reflection = new \ReflectionClass($class);
            $checked++;

            if (\count($reflection->getAttributes(IsGranted::class)) > 0) {
                continue;
            }

            if ($reflection->hasMethod($method) && \count($reflection->getMethod($method)->getAttributes(IsGranted::class)) > 0) {
                continue;
            }

            $missing[] = \sprintf('%s (%s) => %s', $name, $route->getPath(), $controller);
        }

        self::assertGreaterThan(100, $checked, 'Expected to check more core routes, is the router configured?');
        self::assertEquals([], $missing, 'Routes without #[IsGranted] attribute found');
    }
}
