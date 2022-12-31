<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Timesheet;

use App\Export\Timesheet\PDFRenderer;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\MPdfConverter;
use App\Project\ProjectStatisticService;
use App\Tests\Mocks\FileHelperFactory;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * @covers \App\Export\Base\PDFRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Timesheet\PDFRenderer
 * @group integration
 */
class PdfRendererTest extends AbstractRendererTest
{
    public function testConfiguration()
    {
        $sut = new PDFRenderer(
            $this->createMock(Environment::class),
            $this->createMock(HtmlToPdfConverter::class),
            $this->createMock(ProjectStatisticService::class)
        );

        $this->assertEquals('pdf', $sut->getId());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $stack = self::getContainer()->get('request_stack');
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $converter = new MPdfConverter((new FileHelperFactory($this))->create(), $cacheDir);
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        $sut = new PDFRenderer($twig, $converter, $this->createMock(ProjectStatisticService::class));

        $response = $this->render($sut);

        $prefix = date('Ymd');
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.pdf', $response->headers->get('Content-Disposition'));

        $this->assertNotEmpty($response->getContent());
    }
}
