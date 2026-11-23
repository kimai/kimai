<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\VersionCommand;
use App\Constants;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(VersionCommand::class)]
#[Group('integration')]
class VersionCommandTest extends KernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $this->application->add(new VersionCommand());
    }

    #[DataProvider('getTestData')]
    public function testVersion(array $options, $result): void
    {
        $commandTester = $this->getCommandTester($options);
        $output = $commandTester->getDisplay();
        self::assertEquals($result . PHP_EOL, $output);
    }

    public static function getTestData(): array // @phpstan-ignore missingType.iterableValue
    {
        return [
            [[], 'Kimai ' . Constants::VERSION . ' by Kevin Papst.'],
            [['--short' => true], Constants::VERSION],
            [['--number' => true], Constants::VERSION_ID],
        ];
    }

    protected function getCommandTester(array $options = []): CommandTester
    {
        $command = $this->application->find('kimai:version');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], $options);
        $commandTester->execute($inputs);

        return $commandTester;
    }
}
