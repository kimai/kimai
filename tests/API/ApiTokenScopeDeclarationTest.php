<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\API\Permission\ApiTokenScopeMap;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Guard test: every core API endpoint (App\API) must declare an #[ApiToken]
 * attribute (a scope or an explicit "ignore"), so a new endpoint can never
 * silently bypass the API-token scope enforcement.
 *
 * @see \App\API\Attribute\ApiToken
 */
#[Group('integration')]
class ApiTokenScopeDeclarationTest extends KernelTestCase
{
    public function testEveryCoreApiEndpointDeclaresScope(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $router = $container->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);

        $scopeMap = new ApiTokenScopeMap($router, sys_get_temp_dir());
        $map = $scopeMap->build();

        $missing = [];
        foreach ($router->getRouteCollection() as $routeName => $route) {
            $controller = $route->getDefault('_controller');
            if (!\is_string($controller) || !str_contains($controller, '::')) {
                continue;
            }

            [$class] = explode('::', $controller, 2);

            // only core API controllers - plugins may opt in later
            if (!str_starts_with($class, 'App\\API\\')) {
                continue;
            }

            if (!\array_key_exists($routeName, $map)) {
                $missing[] = $routeName . ' (' . $controller . ')';
            }
        }

        self::assertSame(
            [],
            $missing,
            "The following core API endpoints are missing an #[ApiToken] declaration:\n" . implode("\n", $missing)
        );
    }
}
