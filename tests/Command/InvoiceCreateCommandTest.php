<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\InvoiceCreateCommand;
use App\DataFixtures\UserFixtures;
use App\Entity\Customer;
use App\Entity\Project;
use App\Invoice\ServiceInvoice;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\InvoiceTemplateFixtures;
use App\Tests\DataFixtures\ProjectFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\KernelTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\InvoiceCreateCommand
 * @group integration
 */
class InvoiceCreateCommandTest extends KernelTestCase
{
    use KernelTestTrait;

    protected Application $application;

    private function clearInvoiceFiles(): void
    {
        $path = __DIR__ . '/../_data/invoices/';

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
        $this->clearInvoiceFiles();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearInvoiceFiles();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::getContainer();

        $this->application->add(new InvoiceCreateCommand(
            $container->get(ServiceInvoice::class),
            $container->get(CustomerRepository::class),
            $container->get(ProjectRepository::class),
            $container->get(InvoiceTemplateRepository::class),
            $container->get(UserRepository::class),
            $container->get('event_dispatcher')
        ));
    }

    /**
     * Allowed option: user
     * Allowed option: start
     * Allowed option: end
     * Allowed option: timezone
     * Allowed option: customer
     * Allowed option: template
     * Allowed option: search
     * Allowed option: exported
     * Allowed option: by-customer
     * Allowed option: by-project
     * Allowed option: set-exported
     * Allowed option: template-meta
     *
     * @param array $options
     * @return CommandTester
     */
    protected function createInvoice(array $options = []): CommandTester
    {
        $command = $this->application->find('kimai:invoice:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array_merge($options, [
            'command' => $command->getName(),
        ]));

        return $commandTester;
    }

    protected function assertCommandErrors(array $options = [], string $errorMessage = ''): void
    {
        $commandTester = $this->createInvoice($options);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] ' . $errorMessage, $output);
    }

    public function testCreateWithUnknownExportFilter(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'foo'], 'Unknown "exported" filter given');
    }

    public function testCreateWithMissingUser(): void
    {
        $this->assertCommandErrors([], 'You must set a "user" to create invoices');
    }

    public function testCreateWithInvalidUser(): void
    {
        $this->assertCommandErrors(['--user' => 'assdfd'], 'The given username "assdfd" could not be resolved');
    }

    public function testCreateWithMissingEnd(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--start' => '2020-01-01'], 'You need to supply a end date if a start date was given');
    }

    public function testCreateByCustomerAndByProject(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--by-customer' => null, '--by-project' => null], 'You cannot mix "by-customer" and "by-project"');
    }

    public function testCreateWithMissingGenerationMode(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN], 'Could not determine generation mode');
    }

    public function testCreateWithInvalidStart(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--exported' => 'exported', '--template' => 'x', '--start' => 'öäüß', '--end' => '2020-01-01'], 'Invalid start date given');
    }

    public function testCreateWithInvalidEnd(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'x', '--start' => '2020-01-01', '--end' => 'öäüß'], 'Invalid end date given');
    }

    public function testCreateWithInvalidPreviewDirectory(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'x', '--start' => '2020-01-01', '--end' => '2020-01-02', '--preview' => '/kjhg/'], 'Invalid preview directory given');
    }

    public function testCreateWithInvalidCustomer(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 3, '--template' => 'x'], 'Unknown customer ID: 3');
    }

    public function testCreateWithInvalidProject(): void
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--project' => 3, '--template' => 'x'], 'Unknown project ID: 3');
    }

    public function testCreateInvoice(): void
    {
        $fixture = new InvoiceTemplateFixtures();
        $this->importFixture($fixture);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--set-exported' => null, '--customer' => 1, '--template' => 'Invoice', '--start' => '2020-01-01', '--end' => '2020-03-01']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s)', $output);
        $this->assertStringContainsString('| ID', $output);
        $this->assertStringContainsString('| Customer', $output);
        $this->assertStringContainsString('| Total', $output);
        $this->assertStringContainsString('| Filename', $output);
        $this->assertStringContainsString('0 EUR', $output);
        $this->assertStringContainsString('/tests/_data/invoices/' . ((new \DateTime())->format('Y')) . '-001-Test.html |', $output);
    }

    /**
     * @param \DateTime $start
     * @return array<Customer>
     */
    protected function prepareFixtures(\DateTime $start): array
    {
        $fixture = new InvoiceTemplateFixtures();
        $invoiceTemplate = $this->importFixture($fixture);

        $fixture = new CustomerFixtures();
        $fixture->setAmount(1);
        $fixture->setCallback(function (Customer $customer) use ($invoiceTemplate) {
            $customer->setInvoiceTemplate($invoiceTemplate[0]);
        });
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

    public function testCreateInvoiceByCustomer(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--by-customer' => null, '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByCustomerId(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $imports = $this->prepareFixtures($start);
        $customer = $imports[0]->getId();

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => $customer . ',1', '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByProject(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'all', '--by-project' => null, '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByProjectId(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'all', '--project' => '1', '--template' => 'Invoice', '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByProjectWithPreview(): void
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'all', '--preview' => sys_get_temp_dir(), '--by-project' => null, '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }
}
