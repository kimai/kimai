<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Permission;

use App\API\Attribute\ApiToken;
use Symfony\Component\Routing\RouterInterface;

/**
 * Compiled lookup "route name -> required API-token scope".
 *
 * The map is built once (reflection over all API routes + their #[ApiToken]
 * attributes) and dumped to a cached PHP file by {@see ApiTokenScopeMapWarmer}.
 * At runtime the enforcement subscriber only performs a plain array lookup -
 * no reflection per request.
 *
 * Map values:
 *   ''              -> route is declared but requires no scope (#[ApiToken(ignore: true)])
 *   'resource:action' -> route requires this scope
 *   (key absent)    -> route is not declared -> allowed by default
 */
final class ApiTokenScopeMap
{
    /**
     * @var array<string, string>|null
     */
    private ?array $map = null;

    /**
     * @var array<string, string>
     */
    private const VERB_TO_ACTION = [
        'GET' => ApiTokenScopes::ACTION_READ,
        'POST' => ApiTokenScopes::ACTION_CREATE,
        'PATCH' => ApiTokenScopes::ACTION_UPDATE,
        'PUT' => ApiTokenScopes::ACTION_UPDATE,
        'DELETE' => ApiTokenScopes::ACTION_DELETE,
    ];

    public function __construct(
        private readonly RouterInterface $router,
        // static, code-derived build artifact -> lives in the read-only build dir
        // (which falls back to the cache dir when the two are not split)
        private readonly string $buildDir,
    )
    {
    }

    private function getBuildFile(string $dir): string
    {
        return $dir . '/kimai.api_token_scopes.php';
    }

    /**
     * @return array<string, string>
     */
    public function getMap(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $file = $this->getBuildFile($this->buildDir);
        if (is_file($file)) {
            /** @var array<string, string> $map */
            $map = require $file;
            $this->map = $map;

            return $this->map;
        }

        // dev fallback: build live if the artifact was not warmed yet
        $this->map = $this->build();

        return $this->map;
    }

    /**
     * Rebuilds the map and writes the compiled PHP file. Called by the cache warmer.
     *
     * The file is written to $buildDir when provided (the deploy-time build
     * artifact directory), otherwise to the injected build dir.
     */
    public function warmUp(?string $buildDir = null): void
    {
        $map = $this->build();
        $content = '<?php return ' . var_export($map, true) . ';' . \PHP_EOL;
        file_put_contents($this->getBuildFile($buildDir ?? $this->buildDir), $content);
        $this->map = $map;
    }

    /**
     * The scope required to access a route.
     *
     * @return string|null the required scope, or null if the route needs no scope
     *                     (either undeclared or explicitly ignored)
     */
    public function getRequiredScope(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        $scope = $this->getMap()[$routeName] ?? '';

        return $scope === '' ? null : $scope;
    }

    /**
     * Whether a route carries an explicit #[ApiToken] declaration (used by the guard test).
     */
    public function isDeclared(string $routeName): bool
    {
        return \array_key_exists($routeName, $this->getMap());
    }

    /**
     * The catalog of all known scopes as "resource => [action, ...]" (stable action order).
     *
     * @return array<string, array<string>>
     */
    public function getCatalog(): array
    {
        $catalog = [];
        foreach ($this->getMap() as $scope) {
            if ($scope === '' || !str_contains($scope, ':')) {
                continue;
            }
            [$resource, $action] = explode(':', $scope, 2);
            $catalog[$resource][$action] = $action;
        }

        // enforce a stable action order and drop the string keys
        $result = [];
        foreach ($catalog as $resource => $actions) {
            $ordered = [];
            foreach (ApiTokenScopes::ACTIONS as $action) {
                if (isset($actions[$action])) {
                    $ordered[] = $action;
                }
            }
            // any non-standard actions (e.g. from plugins) appended afterwards
            foreach ($actions as $action) {
                if (!\in_array($action, $ordered, true)) {
                    $ordered[] = $action;
                }
            }
            $result[$resource] = $ordered;
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function build(): array
    {
        $map = [];

        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            $controller = $route->getDefault('_controller');
            if (!\is_string($controller) || !str_contains($controller, '::')) {
                continue;
            }

            [$class, $method] = explode('::', $controller, 2);

            // only care about API controllers (core "\API\" or plugin "\API\")
            if (!str_contains($class, '\\API\\') && !str_starts_with($route->getPath(), '/api')) {
                continue;
            }

            if (!class_exists($class)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);
            if (!$reflectionClass->hasMethod($method)) {
                continue;
            }

            $classAttribute = $this->readAttribute($reflectionClass);
            $methodAttribute = $this->readAttribute($reflectionClass->getMethod($method));

            if ($classAttribute === null && $methodAttribute === null) {
                // undeclared -> allowed by default, not part of the map
                continue;
            }

            $map[$routeName] = $this->resolveScope($routeName, $route->getMethods(), $classAttribute, $methodAttribute);
        }

        ksort($map);

        return $map;
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflection
     */
    private function readAttribute(\ReflectionClass|\ReflectionMethod $reflection): ?ApiToken
    {
        $attributes = $reflection->getAttributes(ApiToken::class);
        if (\count($attributes) === 0) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * @param array<string> $verbs
     */
    private function resolveScope(string $routeName, array $verbs, ?ApiToken $classAttribute, ?ApiToken $methodAttribute): string
    {
        // method declaration wins over class declaration
        if ($methodAttribute !== null) {
            if ($methodAttribute->ignore) {
                return '';
            }
            $resource = $methodAttribute->resource ?? $classAttribute?->resource;
            $action = $methodAttribute->action;
        } else {
            /** @var ApiToken $classAttribute */
            if ($classAttribute->ignore) {
                return '';
            }
            $resource = $classAttribute->resource;
            $action = null;
        }

        if ($resource === null) {
            throw new \RuntimeException(\sprintf('API route "%s" declares #[ApiToken] without a resource.', $routeName));
        }

        $action ??= $this->deriveAction($verbs);
        if ($action === null) {
            throw new \RuntimeException(\sprintf('API route "%s" has no HTTP verb that maps to a scope action.', $routeName));
        }

        return ApiTokenScopes::createScope($resource, $action);
    }

    /**
     * @param array<string> $verbs
     */
    private function deriveAction(array $verbs): ?string
    {
        foreach ($verbs as $verb) {
            if (isset(self::VERB_TO_ACTION[strtoupper($verb)])) {
                return self::VERB_TO_ACTION[strtoupper($verb)];
            }
        }

        return null;
    }
}
