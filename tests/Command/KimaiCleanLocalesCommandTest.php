<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\KimaiCleanLocalesCommand
 * @group integration
 */
class KimaiCleanLocalesCommandTest extends KernelTestCase
{
    public function testCommandName()
    {
        $kernel = self::bootKernel(['environment' => 'test']);
        $application = new Application($kernel);
        $application->add(new KimaiCleanLocalesCommand(realpath(__DIR__ . '/../../'), 'test'));

        $command = $application->find('kimai:clean-locales');
        self::assertTrue($command->isEnabled());
        self::assertInstanceOf(KimaiCleanLocalesCommand::class, $command);
    }

    public function testCommandNameIsNotAvailableInProd()
    {
        $command = new KimaiCleanLocalesCommand(realpath(__DIR__ . '/../../'), 'prod');
        self::assertFalse($command->isEnabled());
    }
}
