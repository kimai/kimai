<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

final class UserAmountPreviousMonth extends AbstractUserRevenuePeriod
{
    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_MONTH], parent::getOptions($options));
    }

    public function getId(): string
    {
        return 'UserAmountPreviousMonth';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, float>
     */
    public function getData(array $options = []): array
    {
        return $this->getRevenue($this->createPreviousMonthStartDate(), $this->createPreviousMonthEndDate(), $options);
    }
}
