<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Activity\ActivityStatisticService;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Export\Base\HtmlRenderer;
use App\Export\Base\RendererTrait;
use App\Project\ProjectStatisticService;
use App\Repository\Query\ExportQuery;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[CoversClass(RendererTrait::class)]
#[CoversClass(HtmlRenderer::class)]
#[Group('integration')]
class HtmlRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(?Environment $environment = null): HtmlRenderer
    {
        return new HtmlRenderer(
            $environment ?? $this->createMock(Environment::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class),
            'foo',
            'bar',
            'export/print.html.twig'
        );
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('foo', $sut->getId());
        self::assertEquals('bar', $sut->getTitle());
        self::assertEquals('html', $sut->getType());
        self::assertFalse($sut->isInternal());
    }

    #[Group('legacy')]
    public function testLegacy(): void
    {
        $sut = $this->getAbstractRenderer();

        $sut->setTemplate('some'); // @phpstan-ignore method.deprecated
        $sut->setId('xxxxxx'); // @phpstan-ignore method.deprecated
        self::assertEquals('xxxxxx', $sut->getId());
    }

    public function testRender(): void
    {
        /** @var Environment $twig */
        $twig = $this->getContainer()->get(Environment::class);

        $sut = $this->getAbstractRenderer($twig);

        $response = $this->render($sut);
        self::assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        self::assertIsString($content);
    }

    public function testRenderConvertsDateAndTimeIntoQueryTimezone(): void
    {
        /** @var Environment $twig */
        $twig = $this->getContainer()->get(Environment::class);

        $sut = $this->getAbstractRenderer($twig);

        $customer = new Customer('Customer Name');
        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setName('activity');
        $activity->setProject($project);

        $user = new User();
        $user->setUserIdentifier('foo-bar');

        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);
        $timesheet->setBegin(new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin')));
        $timesheet->setEnd(new \DateTime('2026-08-21 02:00:00', new \DateTimeZone('Europe/Berlin')));

        $query = new ExportQuery();
        $query->setTimezone(new \DateTimeZone('America/New_York'));
        $query->setBegin(new \DateTime('2026-08-20'));
        $query->setEnd(new \DateTime('2026-08-20'));

        $response = $sut->render([$timesheet], $query);
        $content = $response->getContent();
        self::assertIsString($content);

        self::assertStringContainsString('Date (America/New_York)', $content);
        self::assertStringContainsString('19:00', $content);
        self::assertStringContainsString('20:00', $content);
        self::assertStringNotContainsString('2026-08-21', $content);
    }
}
