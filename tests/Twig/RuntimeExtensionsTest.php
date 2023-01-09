<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\RuntimeExtensions;
use PHPUnit\Framework\TestCase;
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\RuntimeExtensions
 */
class RuntimeExtensionsTest extends TestCase
{
    public function testGetFilters()
    {
        $expected = ['md2html', 'desc2html', 'comment2html', 'comment1line', 'colorize', 'icon'];
        $i = 0;

        $sut = new RuntimeExtensions();
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($expected), $twigFilters);

        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($expected[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $expected = [
            'trigger',
            'actions',
            'get_title',
            'progressbar_color',
            'javascript_translations',
            'form_time_presets',
            'active_timesheets',
            'favorite_timesheets',
            'encore_entry_css_source',
            'render_widget',
            'icon',
            'qr_code_data_uri',
        ];

        $i = 0;

        $sut = new RuntimeExtensions();
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($expected), $twigFunctions);

        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            $this->assertInstanceOf(TwigFunction::class, $filter);
            $this->assertEquals($expected[$i++], $filter->getName());
        }
    }

    public function testGetFilterDefinition()
    {
        $sut = new RuntimeExtensions();
        $filters = $sut->getFilters();

        $found_md2html = false;
        $found_desc2html = false;
        $found_comment2html = false;
        $found_comment1line = false;

        foreach ($filters as $filter) {
            switch ($filter->getName()) {
                case 'md2html':
                    self::assertEquals('html', $filters[0]->getPreEscape());
                    self::assertEquals(['html'], $filters[0]->getSafe(new Node()));
                    $found_md2html = true;
                    break;
                case 'desc2html':
                    self::assertEquals(['html'], $filters[1]->getSafe(new Node()));
                    $found_desc2html = true;
                    break;
                case 'comment2html':
                    self::assertEquals(['html'], $filters[2]->getSafe(new Node()));
                    $found_comment2html = true;
                    break;
                case 'comment1line':
                    self::assertEquals('html', $filters[3]->getPreEscape());
                    self::assertEquals(['html'], $filters[3]->getSafe(new Node()));
                    $found_comment1line = true;
                    break;
            }
        }

        self::assertTrue($found_md2html, 'Missing filter: md2html');
        self::assertTrue($found_desc2html, 'Missing filter: desc2html');
        self::assertTrue($found_comment2html, 'Missing filter: comment2html');
        self::assertTrue($found_comment1line, 'Missing filter: comment1line');
    }
}
