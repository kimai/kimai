<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\User;
use App\Export\Base\PdfTemplateRenderer;
use App\Export\Base\RendererTrait;
use App\Export\ColumnConverter;
use App\Export\DefaultTemplate;
use App\Export\Template;
use App\Export\TemplateInterface;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\PdfRendererTrait;
use App\Project\ProjectStatisticService;
use App\Repository\Query\TimesheetQuery;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

#[CoversClass(PdfRendererTrait::class)]
#[CoversClass(RendererTrait::class)]
#[CoversClass(PdfTemplateRenderer::class)]
#[Group('integration')]
class PdfTemplateRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(Environment $twig, ?TemplateInterface $template = null): PdfTemplateRenderer
    {
        $htmlConverter = $this->createMock(HtmlToPdfConverter::class);
        $projectStatisticService = $this->createMock(ProjectStatisticService::class);

        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn(new User());
        $security->expects($this->any())->method('isGranted')->willReturn(true);
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        if ($template === null) {
            $template = new DefaultTemplate($dispatcher, 'test', 'en', 'bar');
        }
        $converter = new ColumnConverter($dispatcher, $security);

        return new PdfTemplateRenderer(
            $twig,
            $htmlConverter,
            $projectStatisticService,
            $converter,
            $this->createMock(LocaleSwitcher::class),
            $template
        );
    }

    public function testConfiguration(): void
    {
        $sut = $this->getAbstractRenderer($this->createMock(Environment::class));

        self::assertEquals('test', $sut->getId());
        self::assertEquals('bar', $sut->getTitle());
        self::assertEquals('pdf', $sut->getType());
        self::assertTrue($sut->isInternal());
    }

    public function testDefaultTemplateRender(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')->willReturnCallback(function (string $templateName, array $options) {
            if ($templateName !== 'export/renderer.pdf.twig') {
                $this->fail('Wrong template given, expected "export/renderer.pdf.twig"');
            }
            self::assertArrayHasKey('font', $options);
            self::assertArrayHasKey('columns', $options);
            self::assertArrayHasKey('summary', $options);
            self::assertArrayHasKey('template', $options);
            self::assertArrayHasKey('title', $options);
            self::assertArrayHasKey('locale', $options);
            self::assertArrayHasKey('entries', $options);
            self::assertArrayHasKey('query', $options);

            self::assertEquals('sans-serif', $options['font']);
            self::assertEquals([
                'date',
                'begin',
                'end',
                'duration',
                'currency',
                'rate',
                'internal_rate',
                'hourly_rate',
                'fixed_rate',
                'user.alias',
                'user.name',
                'user.email',
                'user.account_number',
                'customer.name',
                'project.name',
                'activity.name',
                'description',
                'billable',
                'tags',
                'type',
                'category',
                'customer.number',
                'project.number',
                'customer.vat_id',
                'project.order_number',
                'timesheet.meta.foo',
                'timesheet.meta.foo2',
                'customer.meta.customer-foo',
                'project.meta.project-foo',
                'project.meta.project-foo2',
                'activity.meta.activity-foo',
                'user.meta.mypref',
            ], array_keys($options['columns']));
            self::assertEquals([], $options['summary']);
            self::assertNull($options['title']);
            self::assertEquals('en', $options['locale']);
            self::assertIsArray($options['entries']);
            self::assertInstanceOf(TimesheetQuery::class, $options['query']);

            return '';
        });

        $sut = $this->getAbstractRenderer($twig);

        $response = $this->render($sut);
        self::assertInstanceOf(Response::class, $response);
    }

    public function testRender(): void
    {
        $template = new Template('test_id', 'My title');
        $template->setLocale('de');
        $template->setOptions([
            'summary_columns' => 'duration,rate,project_budget_money',
            'orientation' => 'landscape',
            'pageSize' => 'Letter',
            'font' => 'freesans',
            'name' => 'My title 2',
        ]);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')->willReturnCallback(function (string $templateName, array $options) use ($template) {
            if ($templateName !== 'export/renderer.pdf.twig') {
                $this->fail('Wrong template given, expected "export/renderer.pdf.twig"');
            }
            self::assertArrayHasKey('font', $options);
            self::assertArrayHasKey('columns', $options);
            self::assertArrayHasKey('summary', $options);
            self::assertArrayHasKey('template', $options);
            self::assertArrayHasKey('title', $options);
            self::assertArrayHasKey('locale', $options);
            self::assertArrayHasKey('entries', $options);
            self::assertArrayHasKey('query', $options);

            self::assertEquals('freesans', $options['font']);
            self::assertEquals([], $options['columns']);
            self::assertEquals(['duration', 'rate', 'project_budget_money'], $options['summary']);
            self::assertSame($options['template'], $template);
            self::assertEquals('My title 2', $options['title']);
            self::assertEquals('de', $options['locale']);
            self::assertIsArray($options['entries']);
            self::assertInstanceOf(TimesheetQuery::class, $options['query']);

            return '';
        });

        $sut = $this->getAbstractRenderer($twig, $template);

        $response = $this->render($sut);
        self::assertInstanceOf(Response::class, $response);

        $prefix = date('Ymd');
        self::assertEquals('application/pdf', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.pdf', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        self::assertIsString($content);
    }
}
