<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\AvatarService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\AvatarService
 */
class AvatarServiceTest extends TestCase
{
    public function testDataDirectory()
    {
        $data = realpath(__DIR__ . '/../../');
        $sut = new AvatarService($data);
        self::assertEquals($data . '/public/avatars', $sut->getStorageDirectory());

        $data = realpath(__DIR__ . '/../../var/data/');
        $sut->setStorageDirectory($data);
        self::assertEquals($data, $sut->getStorageDirectory());
    }
}
