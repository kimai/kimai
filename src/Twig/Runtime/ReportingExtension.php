<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Reporting\ReportingService;
use App\Reporting\ReportInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class ReportingExtension implements RuntimeExtensionInterface
{
    public function __construct(private ReportingService $service)
    {
    }

    /**
     * @param User $user
     * @return ReportInterface[]
     */
    public function getAvailableReports(User $user): array
    {
        return $this->service->getAvailableReports($user);
    }
}
