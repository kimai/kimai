<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\EventExtensions;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\EventExtensions
 */
class EventExtensionsTest extends TestCase
{
    protected function getSut(): EventExtensions
    {
        return new EventExtensions();
    }

    public function testGetFunctions()
    {
        $functions = ['trigger'];
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
}
