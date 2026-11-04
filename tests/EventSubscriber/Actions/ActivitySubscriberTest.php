<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\ActivitySubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(ActivitySubscriber::class)]
class ActivitySubscriberTest extends AbstractActionsSubscriberTestCase
{
    private const ALL_PERMISSIONS = ['view', 'edit', 'permissions', 'view_other_timesheet', 'create_other_timesheet', 'create_own_timesheet', 'delete'];

    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(ActivitySubscriber::class, 'activity');
    }

    /**
     * @param array<string> $permissions
     */
    private function createActivitySubscriber(array $permissions): ActivitySubscriber
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnCallback(
            function (mixed $attribute, mixed $subject = null) use ($permissions): bool {
                return \in_array($attribute, $permissions, true);
            }
        );

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(
            function (string $route, array $parameters = []): string {
                if (\count($parameters) === 0) {
                    return $route;
                }

                return $route . '?' . http_build_query($parameters);
            }
        );

        return new ActivitySubscriber($auth, $router);
    }

    private function createActivity(?int $id = 1, bool $visible = true, bool $global = true): Activity
    {
        $activity = new Activity();
        $activity->setName('foo');
        $activity->setVisible($visible);

        if (!$global) {
            $customer = new Customer('customer');
            $this->setId($customer, 23);

            $project = new Project();
            $project->setName('project');
            $project->setCustomer($customer);
            $this->setId($project, 12);

            $activity->setProject($project);
        }

        if ($id !== null) {
            $this->setId($activity, $id);
        }

        return $activity;
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity::class, 'id');
        $property->setValue($entity, $id);
    }

    /**
     * @param array<string> $permissions
     * @return array<string, array<mixed>>
     */
    private function getActions(Activity $activity, array $permissions, string $view = 'index'): array
    {
        return $this->getActionsForPayload(['activity' => $activity], $permissions, $view);
    }

    /**
     * @param array<mixed> $payload
     * @param array<string> $permissions
     * @return array<string, array<mixed>>
     */
    private function getActionsForPayload(array $payload, array $permissions, string $view = 'index'): array
    {
        $sut = $this->createActivitySubscriber($permissions);
        $event = new PageActionsEvent(new User(), $payload, 'activity', $view);
        $sut->onActions($event);

        return $event->getActions();
    }

    public function testWithoutActivityInPayload(): void
    {
        self::assertEquals([], $this->getActionsForPayload([], self::ALL_PERMISSIONS));
        self::assertEquals([], $this->getActionsForPayload(['foo' => $this->createActivity()], self::ALL_PERMISSIONS));
    }

    public function testWithInvalidActivityInPayload(): void
    {
        $invalid = [
            'null' => null,
            'string' => 'activity',
            'int' => 1,
            'array' => ['id' => 1],
            'object' => new \stdClass(),
            'wrong entity' => (new Project())->setName('project'),
        ];

        foreach ($invalid as $name => $value) {
            self::assertEquals([], $this->getActionsForPayload(['activity' => $value], self::ALL_PERMISSIONS), \sprintf('Failed for payload type "%s"', $name));
        }
    }

    public function testNewActivityHasNoActions(): void
    {
        self::assertEquals([], $this->getActions($this->createActivity(null), self::ALL_PERMISSIONS));
    }

    public function testWithoutPermissions(): void
    {
        self::assertEquals([], $this->getActions($this->createActivity(), []));
    }

    public function testWithAllPermissions(): void
    {
        $activity = $this->createActivity(1, true, false);

        $actions = $this->getActions($activity, self::ALL_PERMISSIONS);

        self::assertEquals(['details', 'edit', 'permissions', 'divider0', 'filter', 'divider1', 'create-timesheet', 'trash'], array_keys($actions));
    }

    public function testDetailsIsAddedWithViewPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['view']);

        self::assertEquals(['details', 'divider0'], array_keys($actions));
        self::assertEquals(['title' => 'details', 'url' => 'activity_details?id=1'], $actions['details']);
    }

    public function testDetailsIsNotAddedOnDetailsView(): void
    {
        $actions = $this->getActions($this->createActivity(), ['view'], 'activity_details');

        self::assertEquals([], $actions);
    }

    public function testDetailsIsNotAddedWithoutViewPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['edit']);

        self::assertArrayNotHasKey('details', $actions);
    }

    public function testEditIsAddedWithEditPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['edit']);

        self::assertEquals(['edit', 'divider0'], array_keys($actions));
        self::assertEquals(['url' => 'admin_activity_edit?id=1', 'class' => 'modal-ajax-form', 'title' => 'edit'], $actions['edit']);
    }

    public function testEditIsNotAModalOnEditView(): void
    {
        $actions = $this->getActions($this->createActivity(), ['edit'], 'edit');

        self::assertEquals(['url' => 'admin_activity_edit?id=1', 'class' => '', 'title' => 'edit'], $actions['edit']);
    }

    public function testPermissionsIsAddedWithPermissionsPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['permissions']);

        self::assertEquals(['permissions', 'divider0'], array_keys($actions));
        self::assertEquals(['title' => 'permissions', 'url' => 'admin_activity_permissions?id=1', 'class' => 'modal-ajax-form'], $actions['permissions']);
    }

    public function testPermissionsIsNotAModalOnPermissionsView(): void
    {
        $actions = $this->getActions($this->createActivity(), ['permissions'], 'permissions');

        self::assertEquals(['title' => 'permissions', 'url' => 'admin_activity_permissions?id=1', 'class' => ''], $actions['permissions']);
    }

    public function testFilterSubmenuForGlobalActivity(): void
    {
        $actions = $this->getActions($this->createActivity(), ['view_other_timesheet']);

        self::assertEquals(['filter', 'divider0'], array_keys($actions));
        self::assertEquals(
            ['children' => ['timesheet' => ['title' => 'timesheet.filter', 'url' => 'admin_timesheet?' . http_build_query(['activities[]' => 1])]]],
            $actions['filter']
        );
    }

    public function testFilterSubmenuForActivityWithProject(): void
    {
        $actions = $this->getActions($this->createActivity(1, true, false), ['view_other_timesheet']);

        $expectedUrl = 'admin_timesheet?' . http_build_query(['activities[]' => 1, 'customers[]' => 23, 'projects[]' => 12]);
        self::assertEquals(
            ['children' => ['timesheet' => ['title' => 'timesheet.filter', 'url' => $expectedUrl]]],
            $actions['filter']
        );
    }

    public function testFilterSubmenuIsNotAddedWithoutPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['view']);

        self::assertArrayNotHasKey('filter', $actions);
    }

    public function testCreateTimesheetWithCreateOtherPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['create_other_timesheet']);

        self::assertEquals(['create-timesheet'], array_keys($actions));
        self::assertEquals(
            ['title' => 'create-timesheet', 'icon' => 'start', 'url' => 'admin_timesheet_create?activity=1', 'class' => 'modal-ajax-form'],
            $actions['create-timesheet']
        );
    }

    public function testCreateTimesheetWithCreateOwnPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['create_own_timesheet']);

        self::assertEquals(
            ['title' => 'create-timesheet', 'icon' => 'start', 'url' => 'timesheet_create?activity=1', 'class' => 'modal-ajax-form'],
            $actions['create-timesheet']
        );
    }

    public function testCreateTimesheetPrefersCreateOtherPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['create_other_timesheet', 'create_own_timesheet']);

        self::assertEquals('admin_timesheet_create?activity=1', $actions['create-timesheet']['url']);
    }

    public function testCreateTimesheetIncludesProject(): void
    {
        $activity = $this->createActivity(1, true, false);

        $actions = $this->getActions($activity, ['create_other_timesheet']);
        self::assertEquals('admin_timesheet_create?activity=1&project=12', $actions['create-timesheet']['url']);

        $actions = $this->getActions($activity, ['create_own_timesheet']);
        self::assertEquals('timesheet_create?activity=1&project=12', $actions['create-timesheet']['url']);
    }

    public function testCreateTimesheetIsNotAddedForInvisibleActivity(): void
    {
        $activity = $this->createActivity(1, false);

        self::assertEquals([], $this->getActions($activity, ['create_other_timesheet']));
        self::assertEquals([], $this->getActions($activity, ['create_own_timesheet']));
    }

    public function testCreateTimesheetIsNotAddedWithoutPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['view']);

        self::assertArrayNotHasKey('create-timesheet', $actions);
    }

    public function testDeleteOnIndexView(): void
    {
        $actions = $this->getActions($this->createActivity(), ['delete']);

        self::assertEquals(['trash'], array_keys($actions));
        self::assertEquals(['url' => 'admin_activity_delete?id=1', 'class' => 'modal-ajax-form text-red', 'title' => 'trash'], $actions['trash']);
    }

    public function testDeleteOnProjectDetailsView(): void
    {
        $actions = $this->getActions($this->createActivity(), ['delete'], 'project_details');

        self::assertArrayHasKey('trash', $actions);
    }

    public function testDeleteIsNotAddedOnOtherViews(): void
    {
        foreach (['edit', 'permissions', 'activity_details', 'custom'] as $view) {
            $actions = $this->getActions($this->createActivity(), ['delete'], $view);
            self::assertArrayNotHasKey('trash', $actions, \sprintf('Failed for view "%s"', $view));
        }
    }

    public function testDeleteIsNotAddedWithoutPermission(): void
    {
        $actions = $this->getActions($this->createActivity(), ['view']);

        self::assertArrayNotHasKey('trash', $actions);
    }
}
