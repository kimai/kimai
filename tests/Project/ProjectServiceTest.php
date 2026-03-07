<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Project;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Event\ProjectCreateEvent;
use App\Event\ProjectCreatePostEvent;
use App\Event\ProjectCreatePreEvent;
use App\Event\ProjectMetaDefinitionEvent;
use App\Event\ProjectUpdatePostEvent;
use App\Event\ProjectUpdatePreEvent;
use App\Project\ProjectService;
use App\Repository\ProjectRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Utils\Context;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(ProjectService::class)]
class ProjectServiceTest extends TestCase
{
    private function getSut(
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
        ?SystemConfiguration $configuration = null
    ): ProjectService {
        $repository = $this->createMock(ProjectRepository::class);

        if ($dispatcher === null) {
            $dispatcher = $this->createMock(EventDispatcherInterface::class);
            $dispatcher->method('dispatch')->willReturnCallback(function ($event) {
                return $event;
            });
        }

        if ($validator === null) {
            $validator = $this->createMock(ValidatorInterface::class);
            $validator->method('validate')->willReturn(new ConstraintViolationList());
        }

        if ($configuration === null) {
            $configuration = SystemConfigurationFactory::createStub(
                ['project' => ['copy_teams_on_create' => false]]
            );
        }

        return new ProjectService($repository, $configuration, $dispatcher, $validator);
    }

    public function testSaveNewProjectHasValidationError(): void
    {
        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($constraints);

        $sut = $this->getSut(null, $validator);

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Validation Failed');

        $sut->saveProject(new Project(), new Context(new User()));
    }

    public function testUpdateDispatchesEvents(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) use ($project) {
            if ($event instanceof ProjectUpdatePostEvent) {
                self::assertSame($project, $event->getProject());
            } elseif ($event instanceof ProjectUpdatePreEvent) {
                self::assertSame($project, $event->getProject());
            } else {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $sut->saveProject($project);
    }

    public function testCreateNewProjectDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if (!$event instanceof ProjectMetaDefinitionEvent && !$event instanceof ProjectCreateEvent) {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $customer = new Customer('foo');
        $project = $sut->createNewProject($customer);

        self::assertSame($customer, $project->getCustomer());
    }

    public function testSaveNewProjectDispatchesEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))->method('dispatch')->willReturnCallback(function ($event) {
            if (!$event instanceof ProjectCreatePreEvent && !$event instanceof ProjectCreatePostEvent) {
                $this->fail('Invalid event received');
            }

            return $event;
        });

        $sut = $this->getSut($dispatcher);

        $project = new Project();
        $sut->saveProject($project, new Context(new User()));
        self::assertCount(0, $project->getTeams());
    }

    public function testCreateNewProjectCopiesTeam(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $configuration = SystemConfigurationFactory::createStub(
            ['project' => ['copy_teams_on_create' => true]]
        );

        $sut = $this->getSut($dispatcher, null, $configuration);

        $team1 = new Team('foo');
        $team2 = new Team('bar');

        $user = new User();
        $user->addTeam($team1);
        $user->addTeam($team2);

        $project = new Project();
        $sut->saveProject($project, new Context($user));
        self::assertCount(2, $project->getTeams());
    }

    public function testCreateNewProjectWithoutCustomer(): void
    {
        $sut = $this->getSut();

        $project = $sut->createNewProject();
        self::assertNull($project->getCustomer());

        $project = $sut->createNewProject();
        self::assertNull($project->getCustomer());
    }

    #[DataProvider('getTestData')]
    public function testProjectNumber(string $format, int|string|callable $expected): void
    {
        $configuration = SystemConfigurationFactory::createStub([
            'project' => [
                'copy_teams_on_create' => true,
                'number_format' => $format,
            ]
        ]);

        $sut = $this->getSut(null, null, $configuration);
        $project = $sut->createNewProject();

        if (\is_callable($expected)) {
            $expected = $expected();
        }

        self::assertEquals((string) $expected, $project->getNumber());
    }

    /**
     * @return array<int, array<int, string|int|callable>>
     */
    public static function getTestData(): array
    {
        return [
            // simple tests for single calls
            ['{pc,1}', '2'],
            ['{pc,2}', '02'],
            ['{pc,3}', '002'],
            ['{pc,4}', '0002'],
            ['{Y}', function () { return (int) (new \DateTime())->format('Y'); }],
            ['{y}', function () { return (int) (new \DateTime())->format('y'); }],
            ['{M}', function () { return (new \DateTime())->format('m'); }],
            ['{m}', function () { return (int) (new \DateTime())->format('n'); }],
            ['{D}', function () { return (new \DateTime())->format('d'); }],
            ['{d}', function () { return (int) (new \DateTime())->format('j'); }],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{Y,6}', function () { return '00' . (int) (new \DateTime())->format('Y'); }],
            ['{M,3}', function () { return '0' . (new \DateTime())->format('m'); }],
            ['{D,3}', function () { return '0' . (new \DateTime())->format('d'); }],
            // increment dates
            ['{YY}', function () { return (int) (new \DateTime())->format('Y') + 1; }],
            ['{YY+1}', function () { return (int) (new \DateTime())->format('Y') + 1; }],
            ['{YY+2}', function () { return (int) (new \DateTime())->format('Y') + 2; }],
            ['{YY+3}', function () { return (int) (new \DateTime())->format('Y') + 3; }],
            ['{YY-1}', function () { return (int) (new \DateTime())->format('Y') - 1; }],
            ['{YY-2}', function () { return (int) (new \DateTime())->format('Y') - 2; }],
            ['{YY-3}', function () { return (int) (new \DateTime())->format('Y') - 3; }],
            ['{yy}', function () { return (int) (new \DateTime())->format('y') + 1; }],
            ['{yy+1}', function () { return (int) (new \DateTime())->format('y') + 1; }],
            ['{yy+2}', function () { return (int) (new \DateTime())->format('y') + 2; }],
            ['{yy+3}', function () { return (int) (new \DateTime())->format('y') + 3; }],
            ['{yy-1}', function () { return (int) (new \DateTime())->format('y') - 1; }],
            ['{yy-2}', function () { return (int) (new \DateTime())->format('y') - 2; }],
            ['{yy-3}', function () { return (int) (new \DateTime())->format('y') - 3; }],
            ['{MM}', function () { return (int) (new \DateTime())->format('n') + 1; }], // cast to int removes leading zero
            ['{MM+1}', function () { return (int) (new \DateTime())->format('n') + 1; }], // cast to int removes leading zero
            ['{MM+2}', function () { return (int) (new \DateTime())->format('n') + 2; }], // cast to int removes leading zero
            ['{MM+3}', function () { return (int) (new \DateTime())->format('n') + 3; }], // cast to int removes leading zero
            ['{DD}', function () { return (int) (new \DateTime())->format('j') + 1; }], // cast to int removes leading zero
            ['{DD+1}', function () { return (int) (new \DateTime())->format('j') + 1; }], // cast to int removes leading zero
            ['{DD+2}', function () { return (int) (new \DateTime())->format('j') + 2; }], // cast to int removes leading zero
            ['{DD+3}', function () { return (int) (new \DateTime())->format('j') + 3; }], // cast to int removes leading zero
        ];
    }
}
