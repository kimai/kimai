<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\ExportTemplate;

/**
 * @covers \App\Entity\ExportTemplate
 */
class ExportTemplateTest extends AbstractEntityTestCase
{
    public function testDefaultValues(): void
    {
        $sut = new ExportTemplate();
        self::assertNull($sut->getId());
        self::assertNull($sut->getTitle());
        self::assertEquals('csv', $sut->getRenderer());
        self::assertNull($sut->getLanguage());
        self::assertEquals([], $sut->getColumns());
        self::assertEquals([], $sut->getOptions());
    }

    public function testSetter(): void
    {
        $sut = new ExportTemplate();
        self::assertEquals('New', (string) $sut);

        $sut->setTitle('foo');
        self::assertEquals('foo', $sut->getTitle());
        self::assertEquals('foo', (string) $sut);
        $sut->setTitle(null);
        self::assertNull($sut->getTitle());
        self::assertEquals('New', (string) $sut);

        $sut->setRenderer('xlsx');
        self::assertEquals('xlsx', $sut->getRenderer());

        $sut->setLanguage('de');
        self::assertEquals('de', $sut->getLanguage());
        $sut->setLanguage(null);
        self::assertNull($sut->getLanguage());

        $sut->setColumns(['foo', 'bar', 'WORLD']);
        self::assertEquals(['foo', 'bar', 'WORLD'], $sut->getColumns());
        $sut->setColumns(null);
        self::assertEquals([], $sut->getColumns());

        $sut->setOptions(['foo' => 1, 'bar' => true, 'WORLD' => 'HELLO']);
        self::assertEquals(['foo' => 1, 'bar' => true, 'WORLD' => 'HELLO'], $sut->getOptions());
        $sut->setOptions(null);
        self::assertEquals([], $sut->getOptions());
    }

    public function testClone(): void
    {
        $sut = new ExportTemplate();
        $r = new \ReflectionObject($sut);
        $p = $r->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($sut, 13);
        self::assertEquals(13, $sut->getId());

        $sut2 = clone $sut;
        self::assertNull($sut2->getId());
    }
}
