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
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\Extensions
 */
class ExtensionsTest extends TestCase
{
    protected function getSut(): Extensions
    {
        return new Extensions();
    }

    public function testGetFilters()
    {
        $filters = ['docu_link', 'multiline_indent', 'color', 'font_contrast', 'nl2str'];
        $sut = $this->getSut();
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($filters), $twigFilters);
        $i = 0;
        /** @var TwigFilter $filter */
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }

        // make sure that the nl2str filters does proper escaping
        self::assertEquals('nl2str', $twigFilters[4]->getName());
        self::assertEquals('html', $twigFilters[4]->getPreEscape());
        self::assertEquals(['html'], $twigFilters[4]->getSafe(new Node()));
    }

    public function testGetFunctions()
    {
        $functions = ['class_name', 'iso_day_by_name'];
        $sut = $this->getSut();
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            $this->assertInstanceOf(TwigFunction::class, $filter);
            $this->assertEquals($functions[$i++], $filter->getName());
        }
    }

    public function testDocuLink()
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
            $this->assertEquals($expected, $result);
        }
    }

    public function testGetClassName()
    {
        $sut = $this->getSut();
        $this->assertEquals('DateTime', $sut->getClassName(new \DateTime()));
        $this->assertEquals('stdClass', $sut->getClassName(new \stdClass()));
        /* @phpstan-ignore-next-line */
        $this->assertNull($sut->getClassName(''));
        /* @phpstan-ignore-next-line */
        $this->assertNull($sut->getClassName(null));
        $this->assertEquals('App\Entity\User', $sut->getClassName(new User()));
    }

    public function getMultilineTestData()
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
    public function testMultilineIndent($indent, $string, $expected)
    {
        $sut = $this->getSut();
        self::assertEquals(implode("\n", $expected), $sut->multilineIndent($string, $indent));
    }

    /**
     * Just a very short test, as this delegates to Utils/Color
     */
    public function testColor()
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
    public function testFontContrast()
    {
        $sut = $this->getSut();

        self::assertEquals('#000000', $sut->calculateFontContrastColor('#ccc'));
    }

    public function testIsoDayByName()
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

    public function getTestDataReplaceNewline()
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
    public function testReplaceNewline(string $replacer, $input, $expected)
    {
        $sut = $this->getSut();

        self::assertEquals($expected, $sut->replaceNewline($input, $replacer));
    }
}
