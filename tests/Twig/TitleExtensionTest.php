<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\TitleExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\TitleExtension
 */
class TitleExtensionTest extends TestCase
{
    protected function getSut(): TitleExtension
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $translator->method('trans')->willReturn('foo');

        return new TitleExtension($translator);
    }

    public function testGetFunctions()
    {
        $functions = ['get_title'];
        $sut = $this->getSut();
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $function */
        foreach ($twigFunctions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertEquals($functions[$i++], $function->getName());
        }
    }

    public function testGetTitle()
    {
        $sut = $this->getSut();
        $this->assertEquals('Kimai – foo', $sut->generateTitle());
        $this->assertEquals('sdfsdf | Kimai – foo', $sut->generateTitle('sdfsdf | '));
        $this->assertEquals('<b>Kimai</b> ... foo', $sut->generateTitle('<b>', '</b> ... '));
        $this->assertEquals('Kimai | foo', $sut->generateTitle(null, ' | '));
    }
}
