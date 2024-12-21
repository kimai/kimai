<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Plugin;

use App\Plugin\Package;
use App\Plugin\PluginMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Plugin\Package
 */
class PackageTest extends TestCase
{
    public function testGetPackageFileReturnsCorrectFile(): void
    {
        $fileInfo = new \SplFileInfo('path/to/package.zip');
        $metadata = $this->createMock(PluginMetadata::class);
        $package = new Package($fileInfo, $metadata);

        self::assertSame($fileInfo, $package->getPackageFile());
    }

    public function testGetMetadataReturnsCorrectMetadata(): void
    {
        $fileInfo = new \SplFileInfo('path/to/package.zip');
        $metadata = $this->createMock(PluginMetadata::class);
        $package = new Package($fileInfo, $metadata);

        self::assertSame($metadata, $package->getMetadata());
    }
}
