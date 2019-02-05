<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\PDFRenderer;
use Symfony\Component\HttpFoundation\Request;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Export\Renderer\PDFRenderer
 * @covers \App\Export\Renderer\RendererTrait
 */
class PdfRendererTest extends AbstractRendererTest
{

    public function testConfiguration()
    {
        $sut = new PDFRenderer(
            $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertEquals('pdf', $sut->getId());
        $this->assertEquals('pdf', $sut->getTitle());
        $this->assertEquals('pdf', $sut->getIcon());
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

        $sut = new PDFRenderer($twig);

        $response = $this->render($sut);

        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=kimai-export.pdf', $response->headers->get('Content-Disposition'));

        $this->assertNotEmpty($response->getContent());
    }
}
