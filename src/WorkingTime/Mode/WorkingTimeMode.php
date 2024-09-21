<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Mode;

use App\Entity\User;
use App\Form\Model\UserContractModel;
use App\WorkingTime\Calculator\WorkingTimeCalculator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\FormBuilderInterface;

#[AutoconfigureTag]
interface WorkingTimeMode
{
    /**
     * Short and unique identifier for this mode.
     */
    public function getId(): string;

    /**
     * @return int<0, 100>
     */
    public function getOrder(): int;

    /**
     * Translation key for the name of this mode.
     */
    public function getName(): string;

    /**
     * @param FormBuilderInterface<UserContractModel> $builder
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void;

    public function getCalculator(User $user): WorkingTimeCalculator;

    /**
     * @return array<int, string>
     */
    public function getFormFields(): array;
}
