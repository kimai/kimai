<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\SearchTerm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchTerm::class)]
class SearchTermTest extends TestCase
{
    public function testNormalSearchTerm(): void
    {
        $sut = new SearchTerm('foo bar test 1');
        self::assertEquals('foo bar test 1', $sut->getSearchTerm());
        self::assertEmpty($sut->getSearchFields());
        self::assertTrue($sut->hasSearchTerm());
        self::assertEquals('foo bar test 1', $sut->getOriginalSearch());
        self::assertEquals('foo bar test 1', (string) $sut);
        $expectedParts = [
            ['foo', null, false],
            ['bar', null, false],
            ['test', null, false],
            ['1', null, false],
        ];
        $i = 0;
        foreach ($sut->getParts() as $part) {
            $expected = $expectedParts[$i++];
            self::assertEquals($expected[0], $part->getTerm());
            self::assertEquals($expected[1], $part->getField());
            self::assertEquals($expected[2], $part->isExcluded());
        }
    }

    public function testWithMetaField(): void
    {
        $sut = new SearchTerm('foo:bar');
        self::assertFalse($sut->hasSearchTerm());
        self::assertEquals('', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertEquals(['foo' => 'bar'], $sut->getSearchFields());
        self::assertEquals('foo:bar', $sut->getOriginalSearch());
        self::assertCount(1, $sut->getParts());
        $expectedParts = [
            ['foo', 'bar', false],
        ];
        $i = 0;
        foreach ($sut->getParts() as $part) {
            $expected = $expectedParts[$i++];
            self::assertEquals($expected[0], $part->getField());
            self::assertEquals($expected[1], $part->getTerm());
            self::assertEquals($expected[2], $part->isExcluded());
        }
    }

    #[Group('legacy')]
    public function testWithMultipleMetaFields(): void
    {
        $sut = new SearchTerm('foo:bar bar:foo');
        self::assertFalse($sut->hasSearchTerm());
        self::assertEquals('', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertFalse($sut->hasSearchField('test')); // @phpstan-ignore method.deprecated
        self::assertTrue($sut->hasSearchField('foo')); // @phpstan-ignore method.deprecated
        self::assertTrue($sut->hasSearchField('bar')); // @phpstan-ignore method.deprecated
        self::assertEquals('bar', $sut->getSearchField('foo')); // @phpstan-ignore method.deprecated
        self::assertEquals('foo', $sut->getSearchField('bar')); // @phpstan-ignore method.deprecated
        self::assertEquals(['foo' => 'bar', 'bar' => 'foo'], $sut->getSearchFields());
        self::assertEquals('foo:bar bar:foo', $sut->getOriginalSearch());
        self::assertCount(2, $sut->getParts());
    }

    public function testComplexWithMultipleAndDuplicateMetaFields(): void
    {
        $sut = new SearchTerm('foo:bar hello bar:!foo world test foo:bar2 wuff');
        self::assertTrue($sut->hasSearchTerm());
        self::assertEquals('hello world test wuff', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertEquals(['foo' => 'bar2', 'bar' => 'foo'], $sut->getSearchFields());
        self::assertEquals('foo:bar hello bar:!foo world test foo:bar2 wuff', $sut->getOriginalSearch());
        self::assertCount(7, $sut->getParts());
        $expectedParts = [
            ['bar', 'foo', false],
            ['hello', null, false],
            ['foo', 'bar', true],
            ['world', null, false],
            ['test', null, false],
            ['bar2', 'foo', false],
            ['wuff', null, false],
        ];
        $i = 0;
        foreach ($sut->getParts() as $part) {
            $expected = $expectedParts[$i++];
            self::assertEquals($expected[0], $part->getTerm());
            self::assertEquals($expected[1], $part->getField());
            self::assertEquals($expected[2], $part->isExcluded());
        }
    }

    public function testIssue5221(): void
    {
        $sut = new SearchTerm('ABC-123: abcd: abcd');
        self::assertTrue($sut->hasSearchTerm());
        self::assertEquals('ABC-123: abcd: abcd', $sut->getSearchTerm());
        self::assertEmpty($sut->getSearchFields());
        self::assertEquals([], $sut->getSearchFields());
        self::assertEquals('ABC-123: abcd: abcd', $sut->getOriginalSearch());
        self::assertCount(3, $sut->getParts());
        $expectedParts = [
            ['ABC-123:', null, false],
            ['abcd:', null, false],
            ['abcd', null, false],
        ];
        $i = 0;
        foreach ($sut->getParts() as $part) {
            $expected = $expectedParts[$i++];
            self::assertEquals($expected[0], $part->getTerm());
            self::assertEquals($expected[1], $part->getField());
            self::assertEquals($expected[2], $part->isExcluded());
        }

        $sut = new SearchTerm('1 : 1:.');
        self::assertTrue($sut->hasSearchTerm());
        self::assertEquals('1 :', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertSame([1 => '.'], $sut->getSearchFields()); // this is weird, should be a string, but PHP seems to think otherwise
        self::assertEquals('1 : 1:.', $sut->getOriginalSearch());
        self::assertCount(3, $sut->getParts());
        $expectedParts = [
            ['1', null, false],
            [':', null, false],
            ['.', '1', false],
        ];
        $i = 0;
        foreach ($sut->getParts() as $part) {
            $expected = $expectedParts[$i++];
            self::assertEquals($expected[0], $part->getTerm());
            self::assertEquals($expected[1], $part->getField());
            self::assertEquals($expected[2], $part->isExcluded());
        }
    }

    public function testEmptySearchTerm(): void
    {
        $sut = new SearchTerm('ABC-123:"" abcd:"" abcd');
        self::assertTrue($sut->hasSearchTerm());
        self::assertEquals('abcd', $sut->getSearchTerm());
        self::assertNotEmpty($sut->getSearchFields());
        self::assertEquals(['ABC-123' => '', 'abcd' => ''], $sut->getSearchFields());
        self::assertEquals('ABC-123:"" abcd:"" abcd', $sut->getOriginalSearch());
        self::assertCount(3, $sut->getParts());
        $expectedParts = [
            ['', 'ABC-123', false],
            ['', 'abcd', false],
            ['abcd', null, false],
        ];
        $i = 0;
        foreach ($sut->getParts() as $part) {
            $expected = $expectedParts[$i++];
            self::assertEquals($expected[0], $part->getTerm());
            self::assertEquals($expected[1], $part->getField());
            self::assertEquals($expected[2], $part->isExcluded());
        }
    }
}
