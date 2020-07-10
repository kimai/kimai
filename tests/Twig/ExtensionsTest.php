<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\Extensions;
use PHPUnit\Framework\TestCase;
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
        $filters = ['docu_link', 'multiline_indent'];
        $sut = $this->getSut();
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($filters), $twigFilters);
        $i = 0;
        /** @var TwigFilter $filter */
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $functions = ['class_name'];
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
        $this->assertNull($sut->getClassName(''));
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
}
