<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\ExportTemplate;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExportTemplate::class)]
class ExportTemplateTest extends AbstractEntityTestCase
{
    public function testDefaultValues(): void
    {
        $sut = new ExportTemplate();
        self::assertNull($sut->getId());
        self::assertTrue($sut->isNew());
        self::assertNull($sut->getTitle());
        self::assertEquals('csv', $sut->getRenderer());
        self::assertNull($sut->getLanguage());
        self::assertEquals([], $sut->getColumns());
        self::assertEquals([], $sut->getOptions());
        self::assertNull($sut->getName());
        self::assertNull($sut->getFont());
        self::assertNull($sut->getOrientation());
        self::assertNull($sut->getPageSize());
        self::assertEquals(',', $sut->getSeparator());
        self::assertEquals([], $sut->getSummaryColumns());
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

        $sut->setName('my name is funny');
        $sut->setFont('Helvetica');
        $sut->setPageSize('Letter');
        $sut->setSummaryColumns(['customer', 'rate', 'duration_decimal']);
        self::assertEquals('my name is funny', $sut->getName());
        self::assertEquals('Helvetica', $sut->getFont());
        self::assertEquals('Letter', $sut->getPageSize());
        self::assertEquals(['customer', 'rate', 'duration_decimal'], $sut->getSummaryColumns());

        $sut->setOptions(['foo' => 1, 'bar' => true, 'WORLD' => 'HELLO']);
        self::assertEquals(['foo' => 1, 'bar' => true, 'WORLD' => 'HELLO'], $sut->getOptions());
        $sut->setOption('empty', 123);
        self::assertEquals(['foo' => 1, 'bar' => true, 'WORLD' => 'HELLO', 'empty' => 123], $sut->getOptions());
        $sut->setOption('foo', 4711);
        $sut->setOption('empty', null);
        $sut->setOption('bar', false);
        $sut->setOption('hello', 'kimai');
        self::assertEquals(['foo' => 4711, 'bar' => false, 'WORLD' => 'HELLO', 'hello' => 'kimai'], $sut->getOptions());
        $sut->setOptions(null);
        self::assertEquals([], $sut->getOptions());
    }

    public function testSeparator(): void
    {
        $sut = new ExportTemplate();
        self::assertEquals(',', $sut->getSeparator());
        $sut->setSeparator(';');
        self::assertEquals(';', $sut->getSeparator());
        $sut->setSeparator(',');
        self::assertEquals(',', $sut->getSeparator());

        $this->expectException(\InvalidArgumentException::class);
        $sut->setSeparator('.');
    }

    public function testSetOrientation(): void
    {
        $sut = new ExportTemplate();
        self::assertNull($sut->getOrientation());
        $sut->setOrientation('landscape');
        self::assertEquals('landscape', $sut->getOrientation());
        $sut->setOrientation('PORTRAIT');
        self::assertEquals('portrait', $sut->getOrientation());
        $sut->setOrientation('LandScapE');
        self::assertEquals('landscape', $sut->getOrientation());

        $this->expectException(\InvalidArgumentException::class);
        $sut->setOrientation('vertical');
    }

    public function testClone(): void
    {
        $sut = new ExportTemplate();
        $r = new \ReflectionObject($sut);
        $p = $r->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($sut, 13);
        self::assertEquals(13, $sut->getId());
        self::assertFalse($sut->isNew());

        $sut2 = clone $sut;
        self::assertNull($sut2->getId());
    }
}
