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

    /**
     * @param \Closure(\DateTimeInterface): string $expected
     */
    #[DataProvider('getTestData')]
    public function testProjectNumber(string $format, \Closure $expected): void
    {
        $configuration = SystemConfigurationFactory::createStub([
            'project' => [
                'copy_teams_on_create' => true,
                'number_format' => $format,
            ]
        ]);

        $sut = $this->getSut(null, null, $configuration);

        $date = new \DateTimeImmutable();
        $project = $sut->createNewProject();

        self::assertEquals($expected($date), $project->getNumber());
    }

    public function testProjectNumberIncrementsForMultipleCreateCallsOnSameInstance(): void
    {
        $configuration = SystemConfigurationFactory::createStub([
            'project' => [
                'copy_teams_on_create' => false,
                'number_format' => '{pc,1}',
            ]
        ]);

        $sut = $this->getSut(null, null, $configuration);

        $project1 = $sut->createNewProject();
        $project2 = $sut->createNewProject();
        $project3 = $sut->createNewProject();

        // countProject() is mocked and returns 0, the formatter normalizes increaseBy=0 to 1,
        // so the first generated number is 2. Without the in-instance counter all three
        // calls would re-use "2" — which is exactly the bug reported by the importer.
        self::assertEquals('2', $project1->getNumber());
        self::assertEquals('3', $project2->getNumber());
        self::assertEquals('4', $project3->getNumber());
    }

    public function testProjectNumberSkipsAlreadyExistingNumbers(): void
    {
        $configuration = SystemConfigurationFactory::createStub([
            'project' => [
                'copy_teams_on_create' => false,
                'number_format' => '{pc,1}',
            ]
        ]);

        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('countProject')->willReturn(0);
        // Pretend the database already contains projects with numbers 2 and 3 — the
        // service must skip them and only return the next unused number.
        $repository->method('findOneBy')->willReturnCallback(function (array $criteria): ?Project {
            if (\in_array($criteria['number'] ?? null, ['2', '3'], true)) {
                return new Project();
            }

            return null;
        });

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static fn ($event) => $event);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $sut = new ProjectService($repository, $configuration, $dispatcher, $validator);

        $project1 = $sut->createNewProject();
        $project2 = $sut->createNewProject();

        self::assertEquals('4', $project1->getNumber());
        self::assertEquals('5', $project2->getNumber());
    }

    /**
     * @return array<int, array{0: string, 1: \Closure(\DateTimeInterface): string}>
     */
    public static function getTestData(): array
    {
        $literal = static fn (string $value): \Closure => static fn (): string => $value;
        $date = static fn (string $format): \Closure => static fn (\DateTimeInterface $d): string => $d->format($format);
        $yearLong = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('Y') + $add);
        $yearShort = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('y') + $add);
        $monthShort = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('m') + $add);
        $dayShort = static fn (int $add): \Closure => static fn (\DateTimeInterface $d): string => (string) ((int) $d->format('d') + $add);

        return [
            // simple tests for single calls
            ['{pc,1}', $literal('2')],
            ['{pc,2}', $literal('02')],
            ['{pc,3}', $literal('002')],
            ['{pc,4}', $literal('0002')],
            ['{Y}', $date('Y')],
            ['{y}', $date('y')],
            ['{M}', $date('m')],
            ['{m}', $date('n')],
            ['{D}', $date('d')],
            ['{d}', $date('j')],
            // number formatting
            ['{Y,6}', static fn (\DateTimeInterface $d): string => '00' . $d->format('Y')],
            ['{M,3}', static fn (\DateTimeInterface $d): string => '0' . $d->format('m')],
            ['{D,3}', static fn (\DateTimeInterface $d): string => '0' . $d->format('d')],
            // increment dates
            ['{YY}', $yearLong(1)],
            ['{YY+1}', $yearLong(1)],
            ['{YY+2}', $yearLong(2)],
            ['{YY+3}', $yearLong(3)],
            ['{YY-1}', $yearLong(-1)],
            ['{YY-2}', $yearLong(-2)],
            ['{YY-3}', $yearLong(-3)],
            ['{yy}', $yearShort(1)],
            ['{yy+1}', $yearShort(1)],
            ['{yy+2}', $yearShort(2)],
            ['{yy+3}', $yearShort(3)],
            ['{yy-1}', $yearShort(-1)],
            ['{yy-2}', $yearShort(-2)],
            ['{yy-3}', $yearShort(-3)],
            ['{MM}', $monthShort(1)], // cast to int removes leading zero
            ['{MM+1}', $monthShort(1)], // cast to int removes leading zero
            ['{MM+2}', $monthShort(2)], // cast to int removes leading zero
            ['{MM+3}', $monthShort(3)], // cast to int removes leading zero
            ['{DD}', $dayShort(1)], // cast to int removes leading zero
            ['{DD+1}', $dayShort(1)], // cast to int removes leading zero
            ['{DD+2}', $dayShort(2)], // cast to int removes leading zero
            ['{DD+3}', $dayShort(3)], // cast to int removes leading zero
        ];
    }
}
