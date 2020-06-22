<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\KimaiImporterCommand;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\Command\KimaiImporterCommand
 * @group integration
 */
class KimaiImporterCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $encoder = $this->createMock(UserPasswordEncoderInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $validator = $this->createMock(ValidatorInterface::class);

        $this->application->add(new KimaiImporterCommand($encoder, $registry, $validator));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:import-v1');
        self::assertInstanceOf(KimaiImporterCommand::class, $command);
    }
}
