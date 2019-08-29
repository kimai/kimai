<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\SearchTerm;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\SearchTerm
 */
class SearchTermTest extends TestCase
{
    public function testNormalSearchTerm()
    {
        $sut = new SearchTerm('foo bar test 1');
        self::assertEquals('foo bar test 1', $sut->getSearchTerm());
        self::assertEmpty($sut->getSearchFields());
        self::assertFalse($sut->hasSearchField('foo'));
        self::assertTrue($sut->hasSearchTerm());
        self::assertNull($sut->getSearchField('foo'));
        self::assertEquals('foo bar test 1', $sut->getOriginalSearch());
    }

    public function testWithMetaField()
    {
        $sut = new SearchTerm('foo:bar');
        self::assertFalse($sut->hasSearchTerm());
        self::assertEquals('', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertTrue($sut->hasSearchField('foo'));
        self::assertEquals('bar', $sut->getSearchField('foo'));
        self::assertEquals(['foo' => 'bar'], $sut->getSearchFields());
        self::assertEquals('foo:bar', $sut->getOriginalSearch());
    }

    public function testWithMultipleMetaFields()
    {
        $sut = new SearchTerm('foo:bar bar:foo');
        self::assertFalse($sut->hasSearchTerm());
        self::assertEquals('', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertTrue($sut->hasSearchField('foo'));
        self::assertTrue($sut->hasSearchField('bar'));
        self::assertEquals('bar', $sut->getSearchField('foo'));
        self::assertEquals('foo', $sut->getSearchField('bar'));
        self::assertEquals(['foo' => 'bar', 'bar' => 'foo'], $sut->getSearchFields());
        self::assertEquals('foo:bar bar:foo', $sut->getOriginalSearch());
    }

    public function testComplexWithMultipleAndDuplicateMetaFields()
    {
        $sut = new SearchTerm('foo:bar hello bar:foo world test foo:bar wuff');
        self::assertTrue($sut->hasSearchTerm());
        self::assertEquals('hello world test wuff', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertTrue($sut->hasSearchField('foo'));
        self::assertTrue($sut->hasSearchField('bar'));
        self::assertEquals('bar', $sut->getSearchField('foo'));
        self::assertEquals('foo', $sut->getSearchField('bar'));
        self::assertEquals(['foo' => 'bar', 'bar' => 'foo'], $sut->getSearchFields());
        self::assertEquals('foo:bar hello bar:foo world test foo:bar wuff', $sut->getOriginalSearch());
    }
}
