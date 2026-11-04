<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Extractor;

use App\Export\Annotation\Expose;
use App\Export\Annotation\Order;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\ExtractorException;
use App\Tests\Export\Spreadsheet\Entities\DemoFull;
use App\Tests\Export\Spreadsheet\Entities\ExpressionOnMethod;
use App\Tests\Export\Spreadsheet\Entities\ExpressionOnProperty;
use App\Tests\Export\Spreadsheet\Entities\InvalidPermissionOnClass;
use App\Tests\Export\Spreadsheet\Entities\InvalidPermissionOnMethod;
use App\Tests\Export\Spreadsheet\Entities\InvalidPermissionOnProperty;
use App\Tests\Export\Spreadsheet\Entities\MethodRequiresParams;
use App\Tests\Export\Spreadsheet\Entities\MissingExpressionOnClass;
use App\Tests\Export\Spreadsheet\Entities\MissingNameOnClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnnotationExtractor::class)]
#[CoversClass(Expose::class)]
#[CoversClass(Order::class)]
#[CoversClass(ExtractorException::class)]
class AnnotationExtractorTest extends TestCase
{
    public function testExtract(): void
    {
        $sut = new AnnotationExtractor();

        $columns = $sut->extract(DemoFull::class);

        self::assertIsArray($columns);
        self::assertCount(10, $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $expected = [
            ['type-time', 'time', new \DateTime(), 'foo', ['class-permission']],
            ['Public-Property', 'string', 'public-property', 'messages', []],
            ['type-date', 'date', new \DateTime(), 'messages', []],
            ['Private-Property', 'integer', 123, 'test', ['property-permission', 'second-permission']],
            ['accessor', 'string', 'accessor-method', 'messages', []],
            ['Protected-Property', 'boolean', false, 'messages', []],
            ['Public-Method', 'string', 'public-method', 'messages', []],
            ['Protected-Method', 'datetime', new \DateTime(), 'messages', []],
            ['duration', 'duration', 12345, 'messages', ['method-permission']],
            ['Private-Method', 'boolean', true, 'bar', []],
        ];

        $i = 0;
        $object = new DemoFull();

        foreach ($expected as $item) {
            $column = $columns[$i++];
            self::assertEquals($item[0], $column->getLabel());
            self::assertEquals($item[1], $column->getType());
            $result = \call_user_func($column->getAccessor(), $object);
            self::assertEquals(get_debug_type($item[2]), get_debug_type($result));
            if (\in_array(get_debug_type($result), ['string', 'int', 'bool', 'float'])) {
                self::assertEquals($item[2], $result);
            }
            self::assertEquals($item[3], $column->getTranslationDomain(), 'Failed translation domain for: ' . $item[0]);
            self::assertEquals($item[4], $column->getPermissions(), 'Failed permissions for: ' . $item[0]);
        }
    }

    public function testExceptionOnInvalidPermissionOnClass(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports string "permissions" on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\InvalidPermissionOnClass::class');

        $sut->extract(InvalidPermissionOnClass::class);
    }

    public function testExceptionOnInvalidPermissionOnProperty(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports string "permissions" on property level hierarchy, check App\Tests\Export\Spreadsheet\Entities\InvalidPermissionOnProperty::$foo');

        $sut->extract(InvalidPermissionOnProperty::class);
    }

    public function testExceptionOnInvalidPermissionOnMethod(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports string "permissions" on method level hierarchy, check App\Tests\Export\Spreadsheet\Entities\InvalidPermissionOnMethod::$getFoo()');

        $sut->extract(InvalidPermissionOnMethod::class);
    }

    public function testExceptionOnInvalidType(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('AnnotationExtractor needs a non-empty class name for work');

        /* @phpstan-ignore argument.type */
        $sut->extract(new \stdClass());
    }

    public function testExceptionOnEmptyString(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('AnnotationExtractor needs a non-empty class name for work');

        $sut->extract('');
    }

    public function testExceptionOnMissingExpression(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose needs the "exp" attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\MissingExpressionOnClass::class');

        $sut->extract(MissingExpressionOnClass::class);
    }

    public function testExceptionOnMissingName(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose needs the "name" attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\MissingNameOnClass::class');

        $sut->extract(MissingNameOnClass::class);
    }

    public function testExceptionExpressionOnProperty(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports the "exp" attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\ExpressionOnProperty::$foo');

        $sut->extract(ExpressionOnProperty::class);
    }

    public function testExceptionExpressionOnMethod(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports the "exp" attribute on method level hierarchy, check App\Tests\Export\Spreadsheet\Entities\ExpressionOnMethod::foo()');

        $sut->extract(ExpressionOnMethod::class);
    }

    public function testExceptionExpressionOnMethodWithRequiredParameters(): void
    {
        $sut = new AnnotationExtractor();

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose does not support method App\Tests\Export\Spreadsheet\Entities\MethodRequiresParams::foo(...) as it has required parameters.');

        $sut->extract(MethodRequiresParams::class);
    }
}
