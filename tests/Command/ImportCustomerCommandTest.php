<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ImportCustomerCommand;
use App\Configuration\FormConfiguration;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\ImportCustomerCommand
 * @group integration
 */
class ImportCustomerCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $customers = $this->createMock(CustomerRepository::class);
        $configuration = $this->createMock(FormConfiguration::class);

        $this->application->add(new ImportCustomerCommand($customers, $configuration));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:import:customer');
        self::assertInstanceOf(ImportCustomerCommand::class, $command);
    }
}
