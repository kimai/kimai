<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Export\ServiceExport;
use App\Twig\TimesheetExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\TimesheetExtension
 */
class TimesheetExtensionTest extends TestCase
{
    protected function getSut(): TimesheetExtension
    {
        $service = new ServiceExport();

        return new TimesheetExtension($service);
    }

    public function testGetFunctions()
    {
        $sut = $this->getSut();
        $functions = ['timesheet_exporter'];
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $function */
        foreach ($twigFunctions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertEquals($functions[$i++], $function->getName());
        }
    }

    public function testGetExporter()
    {
        $sut = $this->getSut();
        self::assertEquals([], $sut->getTimesheetExporter());
    }
}
