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
use App\Entity\CustomerMeta;
use App\Entity\Project;
use App\Invoice\ServiceInvoice;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Tests\DataFixtures\CustomerFixtures;
use App\Tests\DataFixtures\InvoiceFixtures;
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

    /**
     * @var Application
     */
    protected $application;

    private function clearInvoiceFiles()
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
        $this->clearInvoiceFiles();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$container;

        $this->application->add(new InvoiceCreateCommand(
            $container->get(ServiceInvoice::class),
            $container->get(TimesheetRepository::class),
            $container->get(CustomerRepository::class),
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
    protected function createInvoice(array $options = [])
    {
        $command = $this->application->find('kimai:invoice:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array_merge($options, [
            'command' => $command->getName(),
        ]));

        return $commandTester;
    }

    protected function assertCommandErrors(array $options = [], string $errorMessage = '')
    {
        $commandTester = $this->createInvoice($options);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] ' . $errorMessage, $output);
    }

    public function testCreateWithUnknownExportFilter()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'foo'], 'Unknown "exported" filter given');
    }

    public function testCreateWithMissingUser()
    {
        $this->assertCommandErrors([], 'You must set a "user" to create invoices');
    }

    public function testCreateWithInvalidUser()
    {
        $this->assertCommandErrors(['--user' => 'assdfd'], 'The given username "assdfd" could not be resolved');
    }

    public function testCreateWithMissingEnd()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--start' => '2020-01-01'], 'You need to supply a end date if a start date was given');
    }

    public function testCreateByCustomerAndByProject()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--by-customer' => null, '--by-project' => null], 'You cannot mix "by-customer" and "by-project"');
    }

    public function testCreateWithMissingGenerationMode()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN], 'Could not determine generation mode');
    }

    public function testCreateWithMissingTemplate()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1], 'You must either pass the "template" or "template-meta" option');
    }

    public function testCreateWithInvalidStart()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--exported' => 'exported', '--template' => 'x', '--start' => 'öäüß', '--end' => '2020-01-01'], 'Invalid start date given');
    }

    public function testCreateWithInvalidEnd()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'x', '--start' => '2020-01-01', '--end' => 'öäüß'], 'Invalid end date given');
    }

    public function testCreateWithInvalidPreviewDirectory()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 1, '--template' => 'x', '--start' => '2020-01-01', '--end' => '2020-01-02', '--preview' => '/kjhg/'], 'Invalid preview directory given');
    }

    public function testCreateWithInvalidCustomer()
    {
        $this->assertCommandErrors(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => 3, '--template' => 'x'], 'Unknown customer ID: 3');
    }

    public function testCreateInvoice()
    {
        $fixture = new InvoiceFixtures();
        $this->importFixture($fixture);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--set-exported' => null, '--customer' => 1, '--template' => 'Invoice', '--start' => '2020-01-01', '--end' => '2020-03-01']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('+----+----------+-------+------------- Created 1 invoice(s) --------------------------------------+', $output);
        $this->assertStringContainsString('| ID | Customer | Total | Filename                                                                |', $output);
        $this->assertStringContainsString('+----+----------+-------+-------------------------------------------------------------------------+', $output);
        $this->assertStringContainsString('| 1  | Test     | 0 EUR | /', $output);
        $this->assertStringContainsString('/tests/_data/invoices/2020-001-test.html |', $output);
    }

    protected function prepareFixtures(\DateTime $start)
    {
        $em = $this->getEntityManager();

        $fixture = new CustomerFixtures();
        $fixture->setAmount(1);
        $fixture->setCallback(function (Customer $customer) {
            $meta = new CustomerMeta();
            $meta->setName('template');
            $meta->setValue('Invoice');
            $customer->setMetaField($meta);
        });
        $this->importFixture($fixture);

        $fixture = new ProjectFixtures();
        $fixture->setCustomers([$em->getRepository(Customer::class)->find(2)]);
        $fixture->setAmount(1);
        $this->importFixture($fixture);

        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByName(UserFixtures::USERNAME_SUPER_ADMIN));
        $fixture->setAmount(20);
        $fixture->setStartDate($start);
        $fixture->setProjects([$em->getRepository(Project::class)->find(2)]);
        $this->importFixture($fixture);

        $fixture = new InvoiceFixtures();
        $this->importFixture($fixture);
    }

    public function testCreateInvoiceByCustomer()
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--by-customer' => null, '--template-meta' => 'template', '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByCustomerId()
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--customer' => '2,1', '--template-meta' => 'template', '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByProject()
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'all', '--by-project' => null, '--template-meta' => 'template', '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }

    public function testCreateInvoiceByProjectWithPreview()
    {
        $start = new \DateTime('-2 months');
        $end = new \DateTime();

        $this->prepareFixtures($start);

        $commandTester = $this->createInvoice(['--user' => UserFixtures::USERNAME_SUPER_ADMIN, '--exported' => 'all', '--preview' => sys_get_temp_dir(), '--by-project' => null, '--template-meta' => 'template', '--start' => $start->format('Y-m-d'), '--end' => $end->format('Y-m-d')]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created 1 invoice(s) ', $output);
    }
}
