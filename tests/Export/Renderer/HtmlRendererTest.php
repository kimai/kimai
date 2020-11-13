<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Export\Renderer\HtmlRenderer;
use App\Repository\ProjectRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * @covers \App\Export\Base\HtmlRenderer
 * @covers \App\Export\Base\RendererTrait
 * @covers \App\Export\Renderer\HtmlRenderer
 * @group integration
 */
class HtmlRendererTest extends AbstractRendererTest
{
    public function testConfiguration()
    {
        $sut = new HtmlRenderer(
            $this->createMock(Environment::class),
            new EventDispatcher(),
            $this->createMock(ProjectRepository::class)
        );

        $this->assertEquals('html', $sut->getId());
        $this->assertEquals('print', $sut->getTitle());
        $this->assertEquals('print', $sut->getIcon());
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

        $sut = new HtmlRenderer($twig, new EventDispatcher(), $this->createMock(ProjectRepository::class));

        $response = $this->render($sut);

        $content = $response->getContent();

        $this->assertStringContainsString('<h2>', $content);
        $this->assertStringContainsString('<h3>Summary</h3>', $content);
        $this->assertEquals(1, substr_count($content, 'id="export-summary"'));
        $this->assertEquals(1, substr_count($content, 'id="export-records"'));
        $this->assertEquals(1, substr_count($content, 'id="summary-project"'));
        $this->assertEquals(1, substr_count($content, 'id="summary-activity"'));

        $this->assertStringContainsString('<td>Customer Name</td>', $content);
        $this->assertStringContainsString('<td>project name</td>', $content);
        $this->assertStringContainsString('<td class="duration summary-duration">01:50 h</td>', $content);
        $this->assertStringContainsString('<td class="cost summary-rate">€2,437.12</td>', $content);

        // 5 times in the "full list" and once in the "summary with activities"
        $this->assertEquals(6, substr_count($content, 'activity description'));
        $this->assertEquals(1, substr_count($content, '<td>activity description</td>'));
    }
}
