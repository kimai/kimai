<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\AppConnectPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppConnectPayload::class)]
class AppConnectPayloadTest extends TestCase
{
    /**
     * This payload is a public contract with the Kimai apps.
     * If this test fails, existing apps will break: add fields instead of changing them
     * and raise AppConnectPayload::VERSION if the meaning of a field changed.
     */
    public function testJsonIsStableContract(): void
    {
        $sut = new AppConnectPayload('https://127.0.0.1:8000', '6ccc2932be3a7e8fa1dd2c254');

        self::assertEquals(
            '{"type":"kimai","version":1,"url":"https://127.0.0.1:8000","token":"6ccc2932be3a7e8fa1dd2c254"}',
            $sut->toJson()
        );
    }

    public function testFieldsAreNotRenamed(): void
    {
        $sut = new AppConnectPayload('https://www.kimai.org', 'foo-bar');

        $result = json_decode($sut->toJson(), true);

        self::assertIsArray($result);
        self::assertSame(['type', 'version', 'url', 'token'], array_keys($result));
        self::assertSame('kimai', $result['type']);
        self::assertSame(1, $result['version']);
        self::assertSame('https://www.kimai.org', $result['url']);
        self::assertSame('foo-bar', $result['token']);
    }

    public function testUrlIsTheBaseUrlWithoutApiPath(): void
    {
        // apps append "/api" themselves, just like they do with a manually entered URL
        $sut = new AppConnectPayload('https://www.kimai.org', 'foo-bar');

        self::assertStringNotContainsString('/api', $sut->toJson());
    }

    public function testTrailingSlashIsRemoved(): void
    {
        $sut = new AppConnectPayload('https://www.kimai.org/', 'foo-bar');

        $result = json_decode($sut->toJson(), true);

        self::assertIsArray($result);
        self::assertSame('https://www.kimai.org', $result['url']);
    }

    public function testSubdirectoryIsKept(): void
    {
        $sut = new AppConnectPayload('https://www.kimai.org/kimai/', 'foo-bar');

        $result = json_decode($sut->toJson(), true);

        self::assertIsArray($result);
        self::assertSame('https://www.kimai.org/kimai', $result['url']);
    }

    public function testSlashesAreNotEscaped(): void
    {
        $sut = new AppConnectPayload('https://www.kimai.org/kimai', 'foo-bar');

        self::assertStringNotContainsString('\/', $sut->toJson());
    }
}
