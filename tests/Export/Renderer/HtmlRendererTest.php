<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Activity\ActivityStatisticService;
use App\Entity\User;
use App\Export\Renderer\HtmlRenderer;
use App\Project\ProjectStatisticService;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );

        $this->assertEquals('html', $sut->getId());
        $this->assertEquals('print', $sut->getTitle());
        $this->assertEquals('print', $sut->getIcon());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getUser')->willReturn(new User());

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);
        /** @var AppVariable $app */
        $app = $twig->getGlobals()['app'];
        $twig->addGlobal('app', $app);
        $app->setTokenStorage($tokenStorage);
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

        $this->assertStringContainsString('<h2 id="doc-title" contenteditable="true"', $content);
        $this->assertStringContainsString('<h3 class="card-title" id="doc-summary" contenteditable="true" data-original="Summary">Summary</h3>', $content);
        $this->assertEquals(1, substr_count($content, 'id="export-summary"'));
        $this->assertEquals(1, substr_count($content, 'id="export-records"'));
        $this->assertEquals(1, substr_count($content, 'id="summary-project"'));
        $this->assertEquals(1, substr_count($content, 'id="summary-activity"'));

        $this->assertStringContainsString('<td>Customer Name</td>', $content);
        $this->assertStringContainsString('<td>project name</td>', $content);
        $this->assertStringContainsString('<span class="duration-format" data-duration-decimal="1.94" data-duration="1:56">1:56</span>', $content);
        $this->assertStringContainsString('<td class="cost summary-rate">€2,437.12</td>', $content);
        $this->assertStringContainsString('-€100.92', $content);

        // 5 times in the "full list" and once in the "summary with activities"
        $this->assertEquals(7, substr_count($content, 'activity description'));
        $this->assertEquals(1, substr_count($content, '<td>activity description</td>'));
    }
}
