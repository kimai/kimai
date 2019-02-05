<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\HtmlRenderer;
use Symfony\Component\HttpFoundation\Request;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Export\Renderer\HtmlRenderer
 * @covers \App\Export\Renderer\RendererTrait
 */
class HtmlRendererTest extends AbstractRendererTest
{
    public function testConfiguration()
    {
        $sut = new HtmlRenderer(
            $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertEquals('html', $sut->getId());
        $this->assertEquals('print', $sut->getTitle());
        $this->assertEquals('print', $sut->getIcon());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var \Twig_Environment $twig */
        $twig = $kernel->getContainer()->get('twig');
        $stack = $kernel->getContainer()->get('request_stack');
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();

        $sut = new HtmlRenderer($twig);

        $response = $this->render($sut);

        $content = $response->getContent();

        $this->assertContains('<h2>List of expenses</h2>', $content);
        $this->assertContains('<h3>Summary</h3>', $content);

        $this->assertContains('<td>Customer Name</td>', $content);
        $this->assertContains('<td>project name</td>', $content);
        $this->assertContains('<td class="duration">01:50 h</td>', $content);
        $this->assertContains('<td class="cost">2,437.12 €</td>', $content);

        $this->assertEquals(5, substr_count($content, '<td>activity description</td>'));
    }
}
