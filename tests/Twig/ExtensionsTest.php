<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Constants;
use App\Entity\Activity;
use App\Entity\User;
use App\Twig\Extensions;
use PHPUnit\Framework\TestCase;
use Twig\Node\TextNode;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @covers \App\Twig\Extensions
 */
class ExtensionsTest extends TestCase
{
    protected function getSut(): Extensions
    {
        return new Extensions();
    }

    private function getTest(string $name): TwigTest
    {
        $sut = $this->getSut();
        foreach ($sut->getTests() as $test) {
            if ($test->getName() === $name) {
                return $test;
            }
        }

        throw new \Exception('Unknown twig test: ' . $name);
    }

    public function testGetFilters(): void
    {
        $filters = ['report_date', 'docu_link', 'multiline_indent', 'color', 'font_contrast', 'default_color', 'nl2str'];
        $sut = $this->getSut();
        $twigFilters = $sut->getFilters();
        self::assertCount(\count($filters), $twigFilters);
        $i = 0;

        foreach ($twigFilters as $filter) {
            self::assertInstanceOf(TwigFilter::class, $filter);
            self::assertEquals($filters[$i++], $filter->getName());
        }

        $id = array_search('nl2str', $filters);

        // make sure that the nl2str filters does proper escaping
        self::assertEquals('nl2str', $twigFilters[$id]->getName());
        self::assertEquals('html', $twigFilters[$id]->getPreEscape());
        self::assertEquals(['html'], $twigFilters[$id]->getSafe(new TextNode('', 10)));
    }

    public function testGetFunctions(): void
    {
        $functions = ['report_date', 'class_name', 'iso_day_by_name', 'random_color'];
        $sut = $this->getSut();
        $twigFunctions = $sut->getFunctions();
        self::assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            self::assertInstanceOf(TwigFunction::class, $filter);
            self::assertEquals($functions[$i++], $filter->getName());
        }
    }

    public function testGetTests(): void
    {
        $tests = ['number'];
        $i = 0;

        $sut = $this->getSut();
        $twigTests = $sut->getTests();
        self::assertCount(\count($tests), $twigTests);

        /** @var TwigTest $test */
        foreach ($twigTests as $test) {
            self::assertInstanceOf(TwigTest::class, $test);
            self::assertEquals($tests[$i++], $test->getName());
        }
    }

    public function testDocuLink(): void
    {
        $data = [
            'timesheet.html' => 'https://www.kimai.org/documentation/timesheet.html',
            'timesheet.html#duration-format' => 'https://www.kimai.org/documentation/timesheet.html#duration-format',
            'invoice.html' => 'https://www.kimai.org/documentation/invoice.html',
            '' => 'https://www.kimai.org/documentation/',
        ];

        $sut = $this->getSut();
        foreach ($data as $input => $expected) {
            $result = $sut->documentationLink($input);
            self::assertEquals($expected, $result);
        }
    }

    public function testGetClassName(): void
    {
        $sut = $this->getSut();
        self::assertEquals('DateTime', $sut->getClassName(new \DateTime()));
        self::assertEquals('stdClass', $sut->getClassName(new \stdClass()));
        /* @phpstan-ignore argument.type */
        self::assertNull($sut->getClassName(''));
        /* @phpstan-ignore argument.type */
        self::assertNull($sut->getClassName(null));
        self::assertEquals('App\Entity\User', $sut->getClassName(new User()));
    }

    public static function getMultilineTestData()
    {
        return [
            ['    ', null, ['']],
            ['    ', '', ['']],
            ['    ', 0, ['    0']],
            ['    ', '1dfsdf
sdfsdf' . PHP_EOL . "\n" .
' aksljdfh laksjd hflka sjhdf lakjhsdflak jsdfh' . "\n" .
'dfsdfsdfsdfsdf',
                ['    1dfsdf', '    sdfsdf', '    ', '     aksljdfh laksjd hflka sjhdf lakjhsdflak jsdfh', '    dfsdfsdfsdfsdf']
            ],
            ['###', '2dfsdf' . PHP_EOL .
'sdfsdf' . PHP_EOL .
'' . "\r\n" .
' aksljdfh laksjd hflka sjhdf lakjhsdflak jsdfh' . PHP_EOL .
'dfsdfsdfsdfsdf',
                ['###2dfsdf', '###sdfsdf', '###', '### aksljdfh laksjd hflka sjhdf lakjhsdflak jsdfh', '###dfsdfsdfsdfsdf']
            ],
            ['  ', '3dfsdf' . "\n" .
'sdfsdf' . "\r\n" .
'' . "\n" .
' aksljdfh laksjd hflka sjhdf lakjhsdflak jsdfh' . "\r\n" .
'dfsdfsdfsdfsdf',
                ['  3dfsdf', '  sdfsdf', '  ', '   aksljdfh laksjd hflka sjhdf lakjhsdflak jsdfh', '  dfsdfsdfsdfsdf']
            ],
        ];
    }

    /**
     * @dataProvider getMultilineTestData
     */
    public function testMultilineIndent($indent, $string, $expected): void
    {
        $sut = $this->getSut();
        self::assertEquals(implode("\n", $expected), $sut->multilineIndent($string, $indent));
    }

    /**
     * Just a very short test, as this delegates to Utils/Color
     */
    public function testColor(): void
    {
        $sut = $this->getSut();

        $globalActivity = new Activity();
        self::assertNull($sut->color($globalActivity));
        self::assertEquals(Constants::DEFAULT_COLOR, $sut->color($globalActivity, true));

        $globalActivity->setColor('#000001');
        self::assertEquals('#000001', $sut->color($globalActivity));
        self::assertEquals('#000001', $sut->color($globalActivity, true));
    }

    /**
     * Just a very short test, as this delegates to Utils/Color
     */
    public function testFontContrast(): void
    {
        $sut = $this->getSut();

        self::assertEquals('#000000', $sut->calculateFontContrastColor('#ccc'));
    }

    public function testIsoDayByName(): void
    {
        $sut = $this->getSut();

        self::assertEquals(1, $sut->getIsoDayByName('MoNdAy'));
        self::assertEquals(2, $sut->getIsoDayByName('tuesDAY'));
        self::assertEquals(3, $sut->getIsoDayByName('wednesday'));
        self::assertEquals(4, $sut->getIsoDayByName('thursday'));
        self::assertEquals(5, $sut->getIsoDayByName('FRIday'));
        self::assertEquals(6, $sut->getIsoDayByName('saturday'));
        self::assertEquals(7, $sut->getIsoDayByName('SUNDAY'));
        // invalid days will return 'monday'
        self::assertEquals(1, $sut->getIsoDayByName(''));
        self::assertEquals(1, $sut->getIsoDayByName('sdfgsdf'));
    }

    public static function getTestDataReplaceNewline()
    {
        yield [',', new \stdClass(), new \stdClass()];
        yield [',', null, null];
        yield [',', '', ''];
        yield ['*', PHP_EOL, '*'];
        yield [',', 'foo' . PHP_EOL . 'bar', 'foo,bar'];
        yield [' &ndash; ', 'foo' . PHP_EOL . 'bar', 'foo &ndash; bar'];
        yield [' &ndash; ', "foo\r\nbar\rtest\nhello", 'foo &ndash; bar &ndash; test &ndash; hello'];
    }

    /**
     * @dataProvider getTestDataReplaceNewline
     */
    public function testReplaceNewline(string $replacer, $input, $expected): void
    {
        $sut = $this->getSut();

        self::assertEquals($expected, $sut->replaceNewline($input, $replacer));
    }

    public function testGetRandomColor(): void
    {
        $sut = $this->getSut();

        $this->assertIsValidColor($sut->randomColor());

        $fooColor = $sut->randomColor('foo-bar');
        self::assertIsValidColor($fooColor);
        self::assertEquals($fooColor, $sut->randomColor('foo-bar'));
    }

    public function testGetDefaultColor(): void
    {
        $sut = $this->getSut();

        self::assertEquals('#123456', $sut->defaultColor('#123456'));
        self::assertEquals(Constants::DEFAULT_COLOR, $sut->defaultColor(null));
        self::assertEquals(Constants::DEFAULT_COLOR, $sut->defaultColor());
        self::assertEquals('', $sut->defaultColor(''));
    }

    public function testIsNumeric(): void
    {
        $test = $this->getTest('number');
        self::assertFalse(\call_user_func($test->getCallable(), null));
        self::assertFalse(\call_user_func($test->getCallable(), true));
        self::assertFalse(\call_user_func($test->getCallable(), false));
        self::assertFalse(\call_user_func($test->getCallable(), '1'));
        self::assertTrue(\call_user_func($test->getCallable(), 1));
        self::assertTrue(\call_user_func($test->getCallable(), 1.0));
    }

    private static function assertIsValidColor(string $color)
    {
        self::assertStringStartsWith('#', $color);
        self::assertEquals(7, \strlen($color));
    }
}
