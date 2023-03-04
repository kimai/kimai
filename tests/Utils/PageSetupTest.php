<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Repository\Query\BaseQuery;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\PageSetup
 */
class PageSetupTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new PageSetup('foo-bar');
        self::assertEquals('foo-bar', $sut->getTitle());
        self::assertNull($sut->getActionName());
        self::assertEquals([], $sut->getActionPayload());
        self::assertEquals('index', $sut->getActionView());
        self::assertEquals('messages', $sut->getTranslationDomain());
        self::assertNull($sut->getHelp());
        self::assertNull($sut->getDataTable());
    }

    public function testSetter(): void
    {
        $dataTable = new DataTable('tadaaaaa', new BaseQuery());
        $sut = new PageSetup('foo-bar');
        $sut->setDataTable($dataTable);
        $sut->setActionName('some-action');
        $sut->setHelp('kjhgkjhgkjhg');
        $sut->setActionView('custom');
        $sut->setActionPayload(['foo' => 'bar']);
        $sut->setTranslationDomain('footuluuu');

        self::assertEquals('footuluuu', $sut->getTranslationDomain());
        self::assertEquals('foo-bar', $sut->getTitle());
        self::assertEquals('some-action', $sut->getActionName());
        self::assertEquals(['foo' => 'bar'], $sut->getActionPayload());
        self::assertEquals('custom', $sut->getActionView());
        self::assertEquals('kjhgkjhgkjhg', $sut->getHelp());
        self::assertSame($dataTable, $sut->getDataTable());
    }
}
