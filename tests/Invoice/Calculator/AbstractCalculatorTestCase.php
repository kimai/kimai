<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Calculator;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

abstract class AbstractCalculatorTestCase extends TestCase
{
    abstract protected function getCalculator(): CalculatorInterface;

    public function testCalculatorInterface(): void
    {
        $sut = $this->getCalculator();

        self::assertLessThanOrEqual(20, \strlen($sut->getId()));

        $this->assertEmptyModel($sut);
    }

    private function assertEmptyModel(CalculatorInterface $sut): void
    {
        $model = $this->getEmptyModel();
        self::assertEquals('EUR', $model->getCurrency());

        $sut->setModel($model);

        self::assertEquals(0, $sut->getTotal());
        self::assertEquals(0, $sut->getVat());
        self::assertEquals(0, $sut->getSubtotal());
        self::assertEquals(0, $sut->getTimeWorked());
        self::assertEquals(0, \count($sut->getEntries()));
        self::assertEquals(0, $sut->getTax());
    }

    private function getEmptyModel(): InvoiceModel
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $query = new InvoiceQuery();

        return (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
    }

    protected function assertDescription(CalculatorInterface $sut, $addProject = false, $addActivity = false): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $user = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(1);

        $project = $this->getMockBuilder(Project::class)->onlyMethods(['getId', 'getCustomer', 'getName'])->disableOriginalConstructor()->getMock();
        $project->method('getId')->willReturn(1);
        $project->method('getCustomer')->willReturn($customer);
        $project->method('getName')->willReturn('project description');

        $activity = $this->getMockBuilder(Activity::class)->onlyMethods(['getId', 'getProject', 'getName'])->disableOriginalConstructor()->getMock();
        $activity->method('getId')->willReturn(1);
        $activity->method('getProject')->willReturn($project);
        $activity->method('getName')->willReturn('activity description');

        $query = new InvoiceQuery();
        if ($addProject === true) {
            $query->setProjects([$project]);
        } elseif ($addActivity === true) {
            $query->setActivities([$activity]);
        }

        $timesheet = new Timesheet();
        $timesheet
            ->setDescription('timesheet description')
            ->setBegin(new \DateTime())
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user)
            ->setActivity($activity)
            ->setProject($project);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries([$timesheet]);

        $sut->setModel($model);
        self::assertEquals(1, \count($sut->getEntries()));

        /** @var Timesheet $result */
        $result = $sut->getEntries()[0];
        if ($addProject === true) {
            self::assertEquals('project description', $result->getDescription());
        } elseif ($addActivity === true) {
            self::assertEquals('activity description', $result->getDescription());
        } else {
            self::assertEquals('timesheet description', $result->getDescription());
        }
    }
}
