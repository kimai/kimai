<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizationCheckerFactory extends AbstractMockFactory
{
    /**
     * Answers every permission check with the given result.
     *
     * Pass a map of "permission => bool" to answer them individually, unknown
     * permissions then fall back to $default.
     *
     * @param array<string, bool> $permissions
     */
    public function create(bool $default = true, array $permissions = []): AuthorizationCheckerInterface
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            function ($attribute) use ($default, $permissions): bool {
                if (\is_string($attribute) && \array_key_exists($attribute, $permissions)) {
                    return $permissions[$attribute];
                }

                return $default;
            }
        );

        return $checker;
    }
}
