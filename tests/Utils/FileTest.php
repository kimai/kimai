<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\File;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\File
 */
class FileTest extends TestCase
{
    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @expectedExceptionMessage Unknown file "/kjhgkjhg/jkhgkjhg"
     */
    public function testGetPermissionsOnNonExistingFile()
    {
        $sut = new File();
        $sut->getPermissions('/kjhgkjhg/jkhgkjhg');
    }

    public function testGetPermissionsOnDisallowedDirectory()
    {
        $sut = new File();
        $perms = $sut->getPermissions(__FILE__);
        $this->assertEquals($perms, fileperms(__FILE__));
    }
}
