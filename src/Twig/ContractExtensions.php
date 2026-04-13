<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\User;
use App\WorkingTime\Calculator\WorkingTimeCalculator;
use App\WorkingTime\WorkingTimeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

final class ContractExtensions extends AbstractExtension
{
    /** @var array<string, WorkingTimeCalculator> */
    private array $calculators = [];

    public function __construct(private readonly WorkingTimeService $workingTimeService)
    {
    }

    public function getTests(): array
    {
        return [
            /* @var array{user: User, date: \DateTimeInterface} $values */
            new TwigTest('work_day', function (array $values): bool {
                // TODO remove me in 3.0, deprecate with 2.55
                if (!\array_key_exists('user', $values) || !\array_key_exists('date', $values)) {
                    throw new \Exception('Missing variable "user" or "date" to check for "is work_day');
                }

                return $this->isWorkingDay($values['date'], $values['user']);
            }),
            new TwigTest('working_day', function (\DateTimeInterface $date, User $user): bool {
                return $this->isWorkingDay($date, $user);
            }),
        ];
    }

    private function isWorkingDay(\DateTimeInterface $date, User $user): bool
    {
        if ($user->getId() === null) {
            return false;
        }

        $id = 'user_' . $user->getId();
        if (!\array_key_exists($id, $this->calculators)) {
            $this->calculators[$id] = $this->workingTimeService->getContractMode($user)->getCalculator($user);
        }

        return $this->calculators[$id]->isWorkDay($date);
    }
}
