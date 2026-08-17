<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Webhook;

use App\Webhook\WebhookEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookEvent::class)]
class WebhookEventTest extends TestCase
{
    public function testConstructStoresValues(): void
    {
        $sut = new WebhookEvent('timesheet.created', 'https://example.com/webhook', 'top-secret');

        self::assertSame('timesheet.created', $sut->getName());
        self::assertSame('https://example.com/webhook', $sut->getUrl());
        self::assertSame('top-secret', $sut->getSecret());
    }

    public function testSecretParameterIsMarkedSensitive(): void
    {
        $reflection = new \ReflectionMethod(WebhookEvent::class, '__construct');
        $parameters = $reflection->getParameters();

        self::assertCount(3, $parameters);
        self::assertSame('secret', $parameters[2]->getName());
        self::assertCount(1, $parameters[2]->getAttributes(\SensitiveParameter::class));
    }
}
