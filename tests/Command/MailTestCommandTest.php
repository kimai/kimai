<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\MailTestCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(MailTestCommand::class)]
#[Group('integration')]
class MailTestCommandTest extends KernelTestCase
{
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new MailTestCommand(
            $this->createMock(EventDispatcherInterface::class)
        ));
    }

    public function testCommandName(): void
    {
        $command = $this->application->find('kimai:mail:test');
        self::assertInstanceOf(MailTestCommand::class, $command);
    }
}
