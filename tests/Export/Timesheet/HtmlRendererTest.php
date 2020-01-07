<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Timesheet;

use App\Export\Timesheet\HtmlRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * @covers \App\Export\Timesheet\HtmlRenderer
 * @group integration
 */
class HtmlRendererTest extends AbstractRendererTest
{
    public function testConfiguration()
    {
        $sut = new HtmlRenderer(
            $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock(),
            new EventDispatcher()
        );

        $this->assertEquals('print', $sut->getId());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = $kernel->getContainer()->get('twig');
        $stack = $kernel->getContainer()->get('request_stack');
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        $sut = new HtmlRenderer($twig, new EventDispatcher());

        $response = $this->render($sut);

        $content = $response->getContent();

        $this->assertStringContainsString('>01:50 h<', $content);
    }
}
