<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Mode;

use App\Entity\User;
use App\WorkingTime\Calculator\WorkingTimeCalculator;
use App\WorkingTime\Calculator\WorkingTimeCalculatorNone;
use Symfony\Component\Form\FormBuilderInterface;

class WorkingTimeModeNone implements WorkingTimeMode
{
    final public const ID = 'none';

    public function getId(): string
    {
        return self::ID;
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getFormFields(): array
    {
        return [];
    }

    public function getName(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // nothing to do here
    }

    public function getCalculator(User $user): WorkingTimeCalculator
    {
        return new WorkingTimeCalculatorNone();
    }
}
