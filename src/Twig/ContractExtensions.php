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
                $user = $values['user'];
                if ($user->getId() === null) {
                    return false;
                }

                $id = 'user_' . $user->getId();
                if (!\array_key_exists($id, $this->calculators)) {
                    $this->calculators[$id] = $this->workingTimeService->getContractMode($user)->getCalculator($user);
                }

                $date = $values['date'];

                return $this->calculators[$id]->isWorkDay($date);
            }),
        ];
    }
}
