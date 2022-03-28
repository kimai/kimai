<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Repository\TimesheetRepository;
use Twig\Extension\RuntimeExtensionInterface;

final class TimesheetExtension implements RuntimeExtensionInterface
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    public function activeEntries(User $user, bool $ticktac = true): array
    {
        return $this->repository->getActiveEntries($user, $ticktac);
    }
}
