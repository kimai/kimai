<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Entity\Activity;
use App\Form\Helper\ActivityHelper;
use App\Form\Helper\ProjectHelper;
use App\Form\Type\ActivityType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(ActivityType::class)]
#[CoversClass(ActivityHelper::class)]
#[CoversClass(ProjectHelper::class)]
class ActivityTypeTest extends TypeTestCase
{
    private UrlGeneratorInterface $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = $this->createMock(UrlGeneratorInterface::class);
    }

    private function createActivityType(): ActivityType
    {
        return new ActivityType(
            $this->createMock(ActivityHelper::class),
            $this->createMock(ProjectHelper::class),
            $this->router
        );
    }

    public function testDefaultOptions(): void
    {
        $type = $this->createActivityType();
        $form = $this->factory->create($type);

        $view = $form->createView();

        self::assertEquals('activity', $view->vars['label']);
        self::assertArrayHasKey('data-option-pattern', $view->vars['attr']);
        self::assertArrayNotHasKey('data-create', $view->vars['attr']);
    }

    public function testWithAllowCreate(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('post_activity', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/api/activities');

        $activityHelper = $this->createMock(ActivityHelper::class);
        $activityHelper->expects($this->once())
            ->method('getChoicePattern')
            ->willReturn('{name}');

        $type = $this->createActivityType();
        $form = $this->factory->create($type, null, [
            'allow_create' => true,
        ]);

        $view = $form->createView();

        self::assertEquals('activity', $view->vars['label']);
        self::assertArrayHasKey('data-option-pattern', $view->vars['attr']);
        self::assertArrayHasKey('data-create', $view->vars['attr']);
        self::assertEquals('/api/activities', $view->vars['attr']['data-create']);
    }

    public function testWithAllowCreateAndCustomApiData(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('custom_activity_endpoint', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/api/custom_activity_endpoint');

        $activityHelper = $this->createMock(ActivityHelper::class);
        $activityHelper->expects($this->once())
            ->method('getChoicePattern')
            ->willReturn('{name}');

        $type = $this->createActivityType();
        $form = $this->factory->create($type, null, [
            'allow_create' => true,
            'api_data' => ['create' => 'custom_activity_endpoint'],
        ]);

        $view = $form->createView();

        self::assertArrayHasKey('data-create', $view->vars['attr']);
        self::assertEquals('custom_activity_endpoint', $view->vars['attr']['data-create']);
    }

    public function testWithoutAllowCreate(): void
    {
        $activityHelper = $this->createMock(ActivityHelper::class);
        $activityHelper->expects($this->once())
            ->method('getChoicePattern')
            ->willReturn('{name}');

        $type = $this->createActivityType();
        $form = $this->factory->create($type, null, [
            'allow_create' => false,
        ]);

        $view = $form->createView();

        self::assertArrayHasKey('data-option-pattern', $view->vars['attr']);
        self::assertArrayNotHasKey('data-create', $view->vars['attr']);
    }

    public function testGetChoiceLabel(): void
    {
        $activityHelper = $this->createMock(ActivityHelper::class);
        $activity = new Activity();
        $activity->setName('Test Activity');

        $activityHelper->expects($this->once())
            ->method('getChoiceLabel')
            ->with($activity)
            ->willReturn('Test Activity');

        $type = $this->createActivityType();
        $label = $type->getChoiceLabel($activity);

        self::assertEquals('Test Activity', $label);
    }

    public function testGroupByWithProject(): void
    {
        $projectHelper = $this->createMock(ProjectHelper::class);
        $project = $this->createMock(\App\Entity\Project::class);
        $project->expects($this->once())
            ->method('getName')
            ->willReturn('Test Project');

        $projectHelper->expects($this->once())
            ->method('getChoiceLabel')
            ->with($project)
            ->willReturn('Test Project');

        $type = $this->createActivityType();

        $activity = new Activity();
        $activity->setProject($project);

        $group = $type->groupBy($activity, 'key', 'value');

        self::assertEquals('Test Project', $group);
    }

    public function testGroupByWithoutProject(): void
    {
        $type = $this->createActivityType();

        $activity = new Activity();

        $group = $type->groupBy($activity, 'key', 'value');

        self::assertEquals('', $group);
    }

    public function testGetChoiceAttributesWithProject(): void
    {
        $project = $this->createMock(\App\Entity\Project::class);
        $project->expects($this->once())
            ->method('getId')
            ->willReturn(42);

        $customer = $this->createMock(\App\Entity\Customer::class);
        $customer->expects($this->once())
            ->method('getCurrency')
            ->willReturn('EUR');

        $project->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $type = $this->createActivityType();

        $activity = new Activity();
        $activity->setProject($project);

        $attributes = $type->getChoiceAttributes($activity, 'key', 'value');

        self::assertEquals(['data-project' => 42, 'data-currency' => 'EUR'], $attributes);
    }

    public function testGetChoiceAttributesWithoutProject(): void
    {
        $type = $this->createActivityType();

        $activity = new Activity();

        $attributes = $type->getChoiceAttributes($activity, 'key', 'value');

        self::assertEquals([], $attributes);
    }
}
