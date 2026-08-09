<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Permission;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Builds the compiled "route -> API-token scope" map once at cache warmup, so
 * the enforcement subscriber never has to reflect over controllers at runtime.
 */
final class ApiTokenScopeMapWarmer implements CacheWarmerInterface
{
    public function __construct(private readonly ApiTokenScopeMap $scopeMap)
    {
    }

    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        // static, code-derived artifact -> write to the build dir, falling back to the cache dir
        $this->scopeMap->warmUp($buildDir ?? $cacheDir);

        return [];
    }
}
