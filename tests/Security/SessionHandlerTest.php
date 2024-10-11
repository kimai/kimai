<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Security\SessionHandler;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * @covers \App\Security\SessionHandler
 */
class SessionHandlerTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new SessionHandler(
            $this->createMock(Connection::class),
            new RateLimiterFactory(['id' => 'foo', 'policy' => 'sliding_window'], new InMemoryStorage()),
            new RequestStack(),
        );

        self::assertFalse($sut->isSessionExpired());
    }
}
