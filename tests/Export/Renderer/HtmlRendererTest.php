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
use App\Export\Base\HtmlRenderer;
use App\Project\ProjectStatisticService;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

/**
 * @covers \App\Export\Base\HtmlRenderer
 * @covers \App\Export\Base\RendererTrait
 * @group integration
 */
class HtmlRendererTest extends AbstractRendererTestCase
{
    public function testConfiguration(): void
    {
        $sut = new HtmlRenderer(
            $this->createMock(Environment::class),
            new EventDispatcher(),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );

        self::assertEquals('html', $sut->getId());
        self::assertEquals('print', $sut->getTitle());
    }

    public function testRender(): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getName')->willReturn('Testing');
        $user->method('isAdmin')->willReturn(false);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('getTimezone')->willReturn('America/Edmonton');
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getUser')->willReturn($user);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);
        /** @var AppVariable $app */
        $app = $twig->getGlobals()['app'];
        $twig->addGlobal('app', $app);
        $app->setTokenStorage($tokenStorage);
        /** @var RequestStack $stack */
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

        self::assertStringContainsString('<h2 id="doc-title" contenteditable="true"', $content);
        self::assertStringContainsString('<h3 class="card-title" id="doc-summary" contenteditable="true" data-original="Summary">Summary</h3>', $content);
        self::assertEquals(1, substr_count($content, 'id="export-summary"'));
        self::assertEquals(1, substr_count($content, 'id="export-records"'));
        self::assertEquals(1, substr_count($content, 'id="summary-project"'));
        self::assertEquals(1, substr_count($content, 'id="summary-activity"'));

        self::assertStringContainsString('<td>Customer Name</td>', $content);
        self::assertStringContainsString('<td>project name</td>', $content);
        self::assertStringContainsString('<span class="duration-format" data-duration-decimal="1.94" data-duration="1:56">1:56</span>', $content);
        self::assertStringContainsString('<td class="cost summary-rate">€2,437.12</td>', $content);
        self::assertStringContainsString('-€100.92', $content);

        // 5 times in the "full list" and once in the "summary with activities"
        self::assertEquals(7, substr_count($content, 'activity description'));
        self::assertEquals(1, substr_count($content, '<td>activity description</td>'));
    }
}
