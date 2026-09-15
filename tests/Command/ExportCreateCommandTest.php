<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ExportCreateCommand;
use App\DataFixtures\UserFixtures;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Export\ServiceExport;
use App\Mail\KimaiMailer;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\KernelTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ExportCreateCommand::class)]
#[Group('integration')]
class ExportCreateCommandTest extends KernelTestCase
{
    use KernelTestTrait;

    private Application $application;

    private function clearExportFiles(): void
    {
        $path = __DIR__ . '/../_data/export/';

        if (is_dir($path)) {
            $files = glob($path . '*');
            if ($files === false) {
                return;
            }
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearExportFiles();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearExportFiles();
        $this->application = $this->createApplication();
    }

    private function createApplication($mailer = null): Application
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $container = self::getContainer();

        $application->add(new ExportCreateCommand(
            $container->get(ServiceExport::class), // @phpstan-ignore argument.type
            $container->get(CustomerRepository::class), // @phpstan-ignore argument.type
            $container->get(ProjectRepository::class), // @phpstan-ignore argument.type
            $container->get(TeamRepository::class), // @phpstan-ignore argument.type
            $container->get(UserRepository::class), // @phpstan-ignore argument.type
            $container->get(TranslatorInterface::class), // @phpstan-ignore argument.type
            $mailer ?? $container->get(KimaiMailer::class),
        ));

        return $application;
    }

    /**
     * Allowed option: start
     * Allowed option: end
     * Allowed option: timezone
     * Allowed option: locale
     * Allowed option: customer
     * Allowed option: project
     * Allowed option: team
     * Allowed option: user
     * Allowed option: set-exported
     * Allowed option: template
     * Allowed option: exported
     * Allowed option: directory
     * Allowed option: email
     * Allowed option: subject
     * Allowed option: body
     *
     * @param array $options
     * @return CommandTester
     */
    protected function createExport(array $options = []): CommandTester
    {
        $command = $this->application->find('kimai:export:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array_merge($options, [
            'command' => $command->getName(),
        ]));

        return $commandTester;
    }

    protected function assertCommandErrors(array $options = [], string $errorMessage = ''): void
    {
        $commandTester = $this->createExport($options);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] ' . $errorMessage, $output);
    }

    protected function assertCommandResult(array $options = [], string $message = ''): void
    {
        $commandTester = $this->createExport($options);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[OK] ' . $message, $output);
    }

    public function testCreateWithUnknownExportFilter(): void
    {
        $this->assertCommandErrors(['--exported' => 'foo'], 'Unknown "exported" filter given');
    }

    public function testCreateWithUnknownTemplate(): void
    {
        $this->assertCommandErrors(['--template' => 'foo'], 'Unknown export "template", available are:');
    }

    public function testCreateWithMissingTemplate(): void
    {
        $this->assertCommandErrors([], 'You must pass the "template" option');
    }

    public function testCreateWithInvalidStart(): void
    {
        $this->assertCommandErrors(['--template' => 'csv', '--start' => '202ß-ä1-01'], 'Invalid start date given');
    }

    public function testCreateWithInvalidEnd(): void
    {
        $this->assertCommandErrors(['--template' => 'csv', '--end' => '202ß-ä1-01'], 'Invalid end date given');
    }

    public function testCreateWithInvalidDirectory(): void
    {
        $this->assertCommandErrors(['--template' => 'csv', '--directory' => '/tzuikmnbgtz/'], 'Invalid "directory" given: /tzuikmnbgtz/');
    }

    public function testCreateWithInvalidEmail(): void
    {
        $this->assertCommandErrors(['--template' => 'csv', '--email' => ['tzuikmnbgtz']], 'Invalid "email" given');
    }

    public function testCreateWithInvalidEmails(): void
    {
        $this->assertCommandErrors(['--template' => 'csv', '--email' => ['foo@example.com', 'foo@1']], 'Invalid "email" given');
    }

    public function testCreateWithMissingEntries(): void
    {
        $options = ['--set-exported' => null, '--customer' => [1], '--template' => 'csv', '--start' => '2020-01-01', '--end' => '2020-03-01'];
        $commandTester = $this->createExport($options);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[OK] No entries found, skipping', $output);
    }

    /**
     * @param \DateTime $start
     * @return array{0: Customer, 1: array<Project>}
     */
    private function prepareFixtures(\DateTime $start): array
    {
        $fixture = new CustomerFixtures();
        $fixture->setAmount(1);
        $customer = $this->importFixture($fixture)[0];

        $fixture = new ProjectFixtures();
        $fixture->setCustomers([$customer]);
        $fixture->setAmount(1);
        $project = $this->importFixture($fixture);

        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByName(UserFixtures::USERNAME_SUPER_ADMIN));
        $fixture->setAmount(20);
        $fixture->setStartDate($start);
        $fixture->setProjects($project);
        $this->importFixture($fixture);

        return [$customer, $project];
    }

    public function testCreateExportByCustomer(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $data = $this->prepareFixtures($start);

        $commandTester = $this->createExport(['--template' => 'csv', '--customer' => [$data[0]->getId()], '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d'), '--username' => UserFixtures::USERNAME_SUPER_ADMIN]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Saved export to: ', $output);
    }

    public function testCreateExportConvertsDateAndTimeIntoGivenTimezone(): void
    {
        $em = $this->getEntityManager();

        $customerFixture = new CustomerFixtures();
        $customerFixture->setAmount(1);
        $customer = $this->importFixture($customerFixture)[0];

        $projectFixture = new ProjectFixtures();
        $projectFixture->setCustomers([$customer]);
        $projectFixture->setAmount(1);
        $project = $this->importFixture($projectFixture)[0];

        $activity = new Activity();
        $activity->setName('activity');
        $activity->setProject($project);
        $em->persist($activity);
        $em->flush();

        $timesheet = new Timesheet();
        $timesheet->setUser($this->getUserByName(UserFixtures::USERNAME_SUPER_ADMIN));
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);
        $timesheet->setBegin(new \DateTime('2026-08-21 01:00:00', new \DateTimeZone('Europe/Berlin')));
        $timesheet->setEnd(new \DateTime('2026-08-21 02:00:00', new \DateTimeZone('Europe/Berlin')));
        $em->persist($timesheet);
        $em->flush();

        $directory = __DIR__ . '/../_data/export/';
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        $commandTester = $this->createExport([
            '--template' => 'csv',
            '--project' => [$project->getId()],
            '--start' => '2026-08-20',
            '--end' => '2026-08-20',
            '--timezone' => 'America/New_York',
            '--directory' => $directory,
        ]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Saved export to: ', $output);

        $files = glob($directory . '*.csv');
        self::assertIsArray($files);
        self::assertCount(1, $files);
        $content = file_get_contents($files[0]);
        self::assertIsString($content);

        self::assertStringContainsString('Date (America/New_York)', $content);
        self::assertStringContainsString('2026-08-20,19:00,20:00', $content);
        self::assertStringNotContainsString('2026-08-21', $content);
    }

    public function testCreateExportByProject(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $data = $this->prepareFixtures($start);

        $commandTester = $this->createExport(['--template' => 'csv', '--project' => [$data[1][0]->getId()], '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Saved export to: ', $output);
    }

    public function testCreateExportWithEmail(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);
        $options = ['--template' => 'csv', '--email' => ['foo@example.com', 'foo2@example.com'], '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')];

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->exactly(2))->method('send');

        $application = $this->createApplication($mailer);
        $command = $application->find('kimai:export:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array_merge($options, [
            'command' => $command->getName(),
        ]));

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Send email with report to: foo@example.com', $output);
        self::assertStringContainsString('Send email with report to: foo2@example.com', $output);
    }
}
