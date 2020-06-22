<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\MultiUpdate;

use App\Form\MultiUpdate\MultiUpdateTableDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\MultiUpdate\MultiUpdateTableDTO
 */
class MultiUpdateTableDTOTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new MultiUpdateTableDTO();
        self::assertEmpty($sut->getEntities());
        self::assertEquals(['' => ''], $sut->getActions());
        self::assertNull($sut->getAction());
    }

    public function testSetterAndGetter()
    {
        $sut = new MultiUpdateTableDTO();

        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->addUpdate('foo'));
        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->addDelete('bar'));
        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->addAction('test', 'hello/world'));
        self::assertEquals(
            [
                '' => '',
                'action.edit' => 'foo',
                'action.delete' => 'bar',
                'test' => 'hello/world'
            ],
            $sut->getActions()
        );

        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->setAction('sdfsdfsdf'));
        self::assertEquals('sdfsdfsdf', $sut->getAction());

        self::assertInstanceOf(MultiUpdateTableDTO::class, $sut->setEntities([1, 2, 3, 4, 5, 6, 7, 8, 9, '0815']));
        self::assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, '0815'], $sut->getEntities());
    }
}
