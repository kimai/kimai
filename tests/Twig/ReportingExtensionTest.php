<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\ReportingExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\ReportingExtension
 */
class ReportingExtensionTest extends TestCase
{
    protected function getSut(bool $isGranted = false): ReportingExtension
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->method('isGranted')->willReturn($isGranted);

        return new ReportingExtension($dispatcher, $security);
    }

    public function testGetFunctions()
    {
        $sut = $this->getSut();
        $functions = ['available_reports'];
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $function */
        foreach ($twigFunctions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $this->assertEquals($functions[$i++], $function->getName());
        }
    }

    public function testGetAvailableReports()
    {
        $sut = $this->getSut();
        $reports = $sut->getAvailableReports(new User());
        self::assertIsArray($reports);
        self::assertEmpty($reports);
    }

    public function testGetAvailableReportsWithPermission()
    {
        $sut = $this->getSut(true);
        $reports = $sut->getAvailableReports(new User());
        self::assertIsArray($reports);
        self::assertCount(4, $reports);
    }
}
