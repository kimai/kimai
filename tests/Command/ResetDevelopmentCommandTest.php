<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ResetDevelopmentCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\ResetDevelopmentCommand
 * @group integration
 */
class ResetDevelopmentCommandTest extends KernelTestCase
{
    public function testCommandName(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new ResetDevelopmentCommand('dev'));

        self::assertTrue($application->has('kimai:reset:dev'));
        $command = $application->find('kimai:reset:dev');
        self::assertInstanceOf(ResetDevelopmentCommand::class, $command);
    }

    public function testCommandNameIsNotEnabledInProd(): void
    {
        $sut = new ResetDevelopmentCommand('prod');
        self::assertFalse($sut->isEnabled());
    }
}
