<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Extractor;

use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use App\Export\Spreadsheet\Extractor\ExtractorException;
use App\Tests\Export\Spreadsheet\Entities\DemoFull;
use App\Tests\Export\Spreadsheet\Entities\ExpressionOnMethod;
use App\Tests\Export\Spreadsheet\Entities\ExpressionOnProperty;
use App\Tests\Export\Spreadsheet\Entities\MethodRequiresParams;
use App\Tests\Export\Spreadsheet\Entities\MissingExpressionOnClass;
use App\Tests\Export\Spreadsheet\Entities\MissingNameOnClass;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Spreadsheet\Extractor\AnnotationExtractor
 * @covers \App\Export\Annotation\Expose
 * @covers \App\Export\Annotation\Order
 * @covers \App\Export\Spreadsheet\Extractor\ExtractorException
 */
class AnnotationExtractorTest extends TestCase
{
    public function testExtract()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $columns = $sut->extract(DemoFull::class);

        self::assertIsArray($columns);
        self::assertCount(10, $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $expected = [
            ['label.type-time', 'time', new \DateTime()],
            ['label.Public-Property', 'string', 'public-property'],
            ['label.type-date', 'date', new \DateTime()],
            ['label.Private-Property', 'integer', 123],
            ['label.accessor', 'string', 'accessor-method'],
            ['label.Protected-Property', 'boolean', false],
            ['label.Public-Method', 'string', 'public-method'],
            ['label.Protected-Method', 'datetime', new \DateTime()],
            ['label.duration', 'duration', 12345],
            ['label.Private-Method', 'boolean', true],
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
        }
    }

    public function testExceptionOnInvalidType()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('AnnotationExtractor needs a non-empty class name for work');

        /* @phpstan-ignore-next-line */
        $sut->extract(new \stdClass());
    }

    public function testExceptionOnEmptyString()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('AnnotationExtractor needs a non-empty class name for work');

        $sut->extract('');
    }

    public function testExceptionOnMissingExpression()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose needs an expression attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\MissingExpressionOnClass::class');

        $sut->extract(MissingExpressionOnClass::class);
    }

    public function testExceptionOnMissingName()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose needs a name attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\MissingNameOnClass::class');

        $sut->extract(MissingNameOnClass::class);
    }

    public function testExceptionExpressionOnProperty()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports the expression attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\ExpressionOnProperty::$foo');

        $sut->extract(ExpressionOnProperty::class);
    }

    public function testExceptionExpressionOnMethod()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose only supports the expression attribute on class level hierarchy, check App\Tests\Export\Spreadsheet\Entities\ExpressionOnMethod::foo()');

        $sut->extract(ExpressionOnMethod::class);
    }

    public function testExceptionExpressionOnMethodWithRequiredParameters()
    {
        $sut = new AnnotationExtractor(new AnnotationReader());

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('@Expose does not support method App\Tests\Export\Spreadsheet\Entities\MethodRequiresParams::foo(...), it has required parameters.');

        $sut->extract(MethodRequiresParams::class);
    }
}
