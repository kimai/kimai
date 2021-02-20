<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Export\ServiceExport;
use App\Twig\Runtime\ExporterExtension;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\Runtime\ExporterExtension
 */
class ExporterExtensionTest extends TestCase
{
    protected function getSut(): ExporterExtension
    {
        $service = new ServiceExport();

        return new ExporterExtension($service);
    }

    public function testGetExporter()
    {
        $sut = $this->getSut();
        self::assertEquals([], $sut->getTimesheetExporter());
    }
}
