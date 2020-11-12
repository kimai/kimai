<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ResetCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\ResetCommand
 * @group integration
 */
class ResetCommandTest extends KernelTestCase
{
    public function testCommandName()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new ResetCommand('test'));

        $command = $application->find('kimai:reset-dev');
        self::assertInstanceOf(ResetCommand::class, $command);
    }

    public function testCommandNameIsNotEnabledInProd()
    {
        $command = new ResetCommand('prod');
        self::assertFalse($command->isEnabled());
    }
}
