<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Twig\Runtime\QrCodeExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QrCodeExtension::class)]
class QrCodeExtensionTest extends TestCase
{
    public function testQrCodeDataUriFunctionReturnsPngDataUri(): void
    {
        $sut = new QrCodeExtension();

        $dataUri = $sut->qrCodeDataUriFunction('https://www.kimai.org/');

        self::assertStringStartsWith('data:image/png;base64,', $dataUri);
        $decoded = base64_decode(substr($dataUri, \strlen('data:image/png;base64,')), true);
        self::assertNotFalse($decoded);
        self::assertStringStartsWith("\x89PNG\x0D\x0A\x1A\x0A", $decoded);
    }
}
