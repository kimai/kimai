<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Timesheet;

use App\Activity\ActivityStatisticService;
use App\Export\Timesheet\HtmlRenderer;
use App\Project\ProjectStatisticService;
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
            $this->createMock(Environment::class),
            new EventDispatcher(),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );

        $this->assertEquals('print', $sut->getId());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $stack = self::getContainer()->get('request_stack');
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        $sut = new HtmlRenderer(
            $twig,
            new EventDispatcher(),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );

        $response = $this->render($sut);

        $content = $response->getContent();

        $this->assertStringContainsString('>1:50<', $content);
    }
}
