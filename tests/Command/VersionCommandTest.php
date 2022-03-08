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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\VersionCommand
 * @group integration
 */
class VersionCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $this->application->add(new VersionCommand());
    }

    /**
     * @dataProvider getTestData
     */
    public function testVersion(array $options, $result)
    {
        $commandTester = $this->getCommandTester($options);
        $output = $commandTester->getDisplay();
        $this->assertEquals($result . PHP_EOL, $output);
    }

    public function getTestData()
    {
        return [
            [[], 'Kimai ' . Constants::VERSION . ' by Kevin Papst and contributors.'],
            [['--short' => true], Constants::VERSION],
            [['--number' => true], Constants::VERSION_ID],
            // @deprecated since 1.14.1
            [['--name' => true], Constants::NAME],
            [['--candidate' => true], Constants::STATUS],
            [['--semver' => true], Constants::VERSION . '-' . Constants::STATUS],
        ];
    }

    protected function getCommandTester(array $options = [])
    {
        $command = $this->application->find('kimai:version');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], $options);
        $commandTester->execute($inputs);

        return $commandTester;
    }
}
