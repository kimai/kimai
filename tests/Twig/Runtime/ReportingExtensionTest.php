<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Entity\User;
use App\Reporting\ReportingService;
use App\Twig\Runtime\ReportingExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @covers \App\Twig\Runtime\ReportingExtension
 */
class ReportingExtensionTest extends TestCase
{
    protected function getSut(bool $isGranted): ReportingExtension
    {
        $eventDispatcher = new EventDispatcher();

        $authorization = $this->createMock(AuthorizationCheckerInterface::class);
        $authorization->expects($this->any())->method('isGranted')->willReturn($isGranted);

        $service = new ReportingService($eventDispatcher, $authorization);

        return new ReportingExtension($service);
    }

    public function testRenderWidgetForInvalidValue()
    {
        $sut = $this->getSut(true);
        $reports = $sut->getAvailableReports(new User());

        $this->assertCount(3, $reports);
    }
}
