<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Entity\Timesheet
 * @group integration
 */
class TimesheetValidationTest extends KernelTestCase
{
    use EntityValidationTestTrait;

    protected function getEntity()
    {
        $customer = new Customer('Test Customer');

        $project = new Project();
        $project->setName('Test Project');
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setName('Test');
        $activity->setProject($project);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);
        $entity->setProject($project);

        return $entity;
    }

    public function testValidationNeedsActivity()
    {
        $project = new Project();
        $project->setCustomer(new Customer('foo'));

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setProject($project);
        $entity->setBegin(new \DateTime());

        $this->assertHasViolationForField($entity, 'activity');
    }

    public function testValidationNeedsProject()
    {
        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity(new Activity());
        $entity->setBegin(new \DateTime());

        $this->assertHasViolationForField($entity, 'project');
    }

    public function testValidationProjectMismatch()
    {
        $customer = new Customer('foo');
        $project = (new Project())->setName('foo')->setCustomer($customer);
        $project2 = (new Project())->setName('bar')->setCustomer($customer);
        $activity = (new Activity())->setName('hello-world')->setProject($project);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);
        $entity->setProject($project2);
        $entity->setBegin(new \DateTime());

        $this->assertHasViolationForField($entity, 'project');
    }

    public function testValidationCustomerInvisible()
    {
        $customer = new Customer('foo');
        $customer->setVisible(false);
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setName('hello-world');
        $activity->setProject($project);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);
        $entity->setProject($project);
        $entity->setBegin(new \DateTime());

        $this->assertHasViolationForField($entity, 'customer');
    }

    private function createStoppedTimesheet(Project $project, Activity $activity, ?int $id = null): Timesheet
    {
        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);
        $entity->setProject($project);
        $entity->setBegin(new \DateTime());
        $entity->setEnd(new \DateTime());

        if ($id !== null) {
            $o = new \ReflectionClass($entity);
            $p = $o->getProperty('id');
            $p->setAccessible(true);
            $p->setValue($entity, $id);
            $p->setAccessible(false);
        }

        return $entity;
    }

    public function testValidationCustomerInvisibleDoesNotTriggerOnStoppedEntities()
    {
        $customer = new Customer('foo');
        $customer->setVisible(false);
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setName('hello-world');
        $activity->setProject($project);

        $entity = $this->createStoppedTimesheet($project, $activity, 99);

        $this->assertHasNoViolations($entity);
    }

    public function testValidationCustomerInvisibleDoesTriggerOnNewEntities()
    {
        $customer = new Customer('foo');
        $customer->setVisible(false);
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setName('hello-world');
        $activity->setProject($project);

        $entity = $this->createStoppedTimesheet($project, $activity);

        $this->assertHasViolationForField($entity, 'customer');
    }

    public function testValidationProjectInvisible()
    {
        $customer = new Customer('foo');
        $project = (new Project())->setName('foo')->setCustomer($customer)->setVisible(false);
        $activity = (new Activity())->setName('hello-world')->setProject($project);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);
        $entity->setProject($project);
        $entity->setBegin(new \DateTime());

        $this->assertHasViolationForField($entity, 'project');
    }

    public function testValidationProjectInvisibleDoesNotTriggerOnStoppedEntities()
    {
        $customer = new Customer('foo');
        $project = (new Project())->setName('foo')->setCustomer($customer)->setVisible(false);
        $activity = (new Activity())->setName('hello-world')->setProject($project);

        $entity = $this->createStoppedTimesheet($project, $activity, 1);

        $this->assertHasNoViolations($entity);
    }

    public function testValidationProjectInvisibleDoesTriggerOnNewEntities()
    {
        $customer = new Customer('foo');
        $project = (new Project())->setName('foo')->setCustomer($customer)->setVisible(false);
        $activity = (new Activity())->setName('hello-world')->setProject($project);

        $entity = $this->createStoppedTimesheet($project, $activity);

        $this->assertHasViolationForField($entity, 'project');
    }

    public function testValidationActivityInvisible()
    {
        $customer = new Customer('foo');
        $project = (new Project())->setName('foo')->setCustomer($customer);
        $activity = (new Activity())->setName('hello-world')->setProject($project)->setVisible(false);

        $entity = new Timesheet();
        $entity->setUser(new User());
        $entity->setActivity($activity);
        $entity->setProject($project);
        $entity->setBegin(new \DateTime());

        $this->assertHasViolationForField($entity, 'activity');
    }

    public function testValidationActivityInvisibleDoesNotTriggerOnStoppedEntities()
    {
        $customer = new Customer('foo');
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setName('hello-world');
        $activity->setProject($project);
        $activity->setVisible(false);

        $entity = $this->createStoppedTimesheet($project, $activity, 2);

        $this->assertHasNoViolations($entity);
    }

    public function testValidationActivityInvisibleDoesTriggerOnNewEntities()
    {
        $customer = new Customer('foo');
        $project = new Project();
        $project->setName('foo');
        $project->setCustomer($customer);
        $activity = new Activity();
        $activity->setName('hello-world');
        $activity->setProject($project);
        $activity->setVisible(false);

        $entity = $this->createStoppedTimesheet($project, $activity);

        $this->assertHasViolationForField($entity, 'activity');
    }

    public function testValidationEndNotEarlierThanBegin()
    {
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $end = $end->modify('-1 second');
        $entity->setBegin($begin);
        $entity->setEnd($end);

        $this->assertHasViolationForField($entity, 'end_date');

        // allow same begin and end
        $entity = $this->getEntity();
        $begin = new \DateTime();
        $end = clone $begin;
        $entity->setBegin($begin);
        $entity->setEnd($end);

        $this->assertHasViolationForField($entity, []);
    }
}
