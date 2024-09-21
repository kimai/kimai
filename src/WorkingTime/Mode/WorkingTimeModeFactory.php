<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Mode;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class WorkingTimeModeFactory
{
    /**
     * @param iterable<WorkingTimeMode> $modes
     */
    public function __construct(
        #[TaggedIterator(WorkingTimeMode::class)]
        private readonly iterable $modes
    )
    {
    }

    /**
     * @return WorkingTimeMode[]
     */
    public function getAll(): array
    {
        $modes = [];
        foreach ($this->modes as $mode) {
            $modes[] = $mode;
        }

        return $modes;
    }

    public function getModeForUser(User $user): WorkingTimeMode
    {
        return $this->getMode($user->getWorkContractMode());
    }

    public function getMode(string $contractMode): WorkingTimeMode
    {
        foreach ($this->modes as $mode) {
            if ($mode->getId() === $contractMode) {
                return $mode;
            }
        }

        throw new \InvalidArgumentException('Unknown working contract mode: ' . $contractMode);
    }
}
