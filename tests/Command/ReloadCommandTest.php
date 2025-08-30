<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ReloadCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ReloadCommand::class)]
#[Group('integration')]
class ReloadCommandTest extends KernelTestCase
{
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new ReloadCommand(
            $this->application->getKernel()->getProjectDir(),
            $this->application->getKernel()->getEnvironment()
        ));
    }

    public function testCommandName(): void
    {
        $command = $this->application->find('kimai:reload');
        self::assertInstanceOf(ReloadCommand::class, $command);
    }
}
