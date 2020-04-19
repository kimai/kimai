<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\DatatableExtensions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\DatatableExtensions
 */
class DatatableExtensionsTest extends TestCase
{
    protected function getSut(string $locale): DatatableExtensions
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new DatatableExtensions($requestStack);
    }

    public function testGetFunctions()
    {
        $functions = ['is_visible_column', 'is_datatable_configured'];
        $sut = $this->getSut('de');
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $function */
        foreach ($twigFunctions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertEquals($functions[$i++], $function->getName());
        }
    }
}
