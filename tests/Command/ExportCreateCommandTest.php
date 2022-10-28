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
use App\Entity\Customer;
use App\Entity\Project;
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
use App\Utils\Translator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\ExportCreateCommand
 * @group integration
 */
class ExportCreateCommandTest extends KernelTestCase
{
    use KernelTestTrait;

    /**
     * @var Application
     */
    protected $application;

    private function clearExportFiles()
    {
        $path = __DIR__ . '/../_data/export/';

        if (is_dir($path)) {
            $files = glob($path . '*');
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
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$container;

        $this->application->add(new ExportCreateCommand(
            $container->get(ServiceExport::class),
            $container->get(CustomerRepository::class),
            $container->get(ProjectRepository::class),
            $container->get(TeamRepository::class),
            $container->get(UserRepository::class),
            $container->get(Translator::class),
            $container->get(KimaiMailer::class),
        ));
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
    protected function createExport(array $options = [])
    {
        $command = $this->application->find('kimai:export:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array_merge($options, [
            'command' => $command->getName(),
        ]));

        return $commandTester;
    }

    protected function assertCommandErrors(array $options = [], string $errorMessage = '')
    {
        $commandTester = $this->createExport($options);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] ' . $errorMessage, $output);
    }

    protected function assertCommandResult(array $options = [], string $message = '')
    {
        $commandTester = $this->createExport($options);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] ' . $message, $output);
    }

    public function testCreateWithUnknownExportFilter()
    {
        $this->assertCommandErrors(['--exported' => 'foo'], 'Unknown "exported" filter given');
    }

    public function testCreateWithUnknownTemplate()
    {
        $this->assertCommandErrors(['--template' => 'foo'], 'Unknown export "template", available are:');
    }

    public function testCreateWithMissingTemplate()
    {
        $this->assertCommandErrors([], 'You must pass the "template" option');
    }

    public function testCreateWithInvalidStart()
    {
        $this->assertCommandErrors(['--template' => 'csv', '--start' => '202ß-ä1-01'], 'Invalid start date given');
    }

    public function testCreateWithInvalidEnd()
    {
        $this->assertCommandErrors(['--template' => 'csv', '--end' => '202ß-ä1-01'], 'Invalid end date given');
    }

    public function testCreateWithInvalidDirectory()
    {
        $this->assertCommandErrors(['--template' => 'csv', '--directory' => '/tzuikmnbgtz/'], 'Invalid "directory" given: /tzuikmnbgtz/');
    }

    public function testCreateWithInvalidEmail()
    {
        $this->assertCommandErrors(['--template' => 'csv', '--email' => ['tzuikmnbgtz']], 'Invalid "email" given: tzuikmnbgtz');
    }

    public function testCreateWithInvalidEmails()
    {
        $this->assertCommandErrors(['--template' => 'csv', '--email' => ['foo@example.com', 'foo@1']], 'Invalid "email" given: foo@1');
    }

    public function testCreateWithMissingEntries()
    {
        $this->assertCommandResult(['--set-exported' => null, '--customer' => [1], '--template' => 'csv', '--start' => '2020-01-01', '--end' => '2020-03-01'], 'No entries found, skipping');
    }

    protected function prepareFixtures(\DateTime $start)
    {
        $fixture = new CustomerFixtures();
        $fixture->setAmount(1);
        $customer = $this->importFixture($fixture)[0];

        $fixture = new ProjectFixtures();
        $fixture->setCustomers([$customer]);
        $fixture->setAmount(1);
        $projects = $this->importFixture($fixture);

        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByName(UserFixtures::USERNAME_SUPER_ADMIN));
        $fixture->setAmount(20);
        $fixture->setStartDate($start);
        $fixture->setProjects($projects);
        $this->importFixture($fixture);

        return [$customer];
    }

    public function testCreateExportByCustomer()
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $customers = $this->prepareFixtures($start);

        $commandTester = $this->createExport(['--template' => 'csv', '--customer' => [$customers[0]->getId()], '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Saved export to: ', $output);
    }

    // TODO add test to verify emails
}
