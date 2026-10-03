<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Export\Base\AbstractSpreadsheetRenderer;
use App\Export\Base\XlsxRenderer;
use App\Export\ColumnConverter;
use App\Export\Package\SpoutSpreadsheet;
use App\Export\Renderer\XlsxRendererFactory;
use App\Repository\Query\ExportQuery;
use App\Tests\Export\Renderer\AbstractRendererTestCase;
use App\Tests\Mocks\MetaFieldColumnSubscriberMock;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(AbstractSpreadsheetRenderer::class)]
#[CoversClass(SpoutSpreadsheet::class)]
#[CoversClass(XlsxRenderer::class)]
#[Group('integration')]
class XlsxRendererTest extends AbstractRendererTestCase
{
    protected function getAbstractRenderer(): XlsxRenderer
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn(new User());
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $converter = new ColumnConverter($dispatcher, $security);
        $factory = new XlsxRendererFactory($converter, $dispatcher, $translator);

        return $factory->createDefault();
    }

    public function testConfigurationFromTemplate(): void
    {
        $sut = $this->getAbstractRenderer();

        self::assertEquals('xlsx', $sut->getType());
        self::assertEquals('xlsx', $sut->getId());
        self::assertEquals('xlsx', $sut->getTitle());
        self::assertFalse($sut->isInternal());
        $sut->setInternal(true);
        self::assertTrue($sut->isInternal());
    }

    public function testRender(): void
    {
        $sut = $this->getAbstractRenderer();

        $response = $this->render($sut);
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        $file = $response->getFile();
        $prefix = date('Ymd');
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertEquals('attachment; filename=' . $prefix . '-Customer_Name-project_name.xlsx', $response->headers->get('Content-Disposition'));

        self::assertTrue(file_exists($file->getRealPath()));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        self::assertNotEmpty($content2);

        self::assertFalse(file_exists($file->getRealPath()));
    }

    public function testRenderConvertsDateAndTimeIntoQueryTimezone(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $security->method('isGranted')->willReturn(true);

        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get(TranslatorInterface::class);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriberMock());

        $converter = new ColumnConverter($dispatcher, $security);
        $factory = new XlsxRendererFactory($converter, $dispatcher, $translator);
        $sut = $factory->createDefault();

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

        $response = $sut->render([$timesheet], $query);
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        $file = $response->getFile();
        $spreadsheet = IOFactory::load($file->getRealPath(), 0, ['Xlsx']);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        self::assertEquals('Date (America/New_York)', $rows[0][0]);
        self::assertEquals('2026-08-20', $rows[1][0]);
        self::assertEquals('19:00', $rows[1][1]);
        self::assertEquals('20:00', $rows[1][2]);
    }
}
