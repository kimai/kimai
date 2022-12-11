<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingModeService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TimesheetServiceFactory extends AbstractMockFactory
{
    public function create(): TimesheetService
    {
        $configuration = SystemConfigurationFactory::createStub();
        $repository = $this->createMock(TimesheetRepository::class);
        $repository->method('getActiveEntries')->willReturn([]);
        $service = new TrackingModeService($configuration, []);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);

        return new TimesheetService($configuration, $repository, $service, $dispatcher, $security, $validator);
    }
}
