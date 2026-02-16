<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetException;

abstract class AbstractBillablePercent extends AbstractWidgetType
{
    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
        ], parent::getOptions($options));
    }

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-user-billable-percent.html.twig';
    }

    /**
     * @param array<int|string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        try {
            if(\is_int($options[1]) === false) {
                throw new WidgetException(
                    'Failed loading widget data: Wrong type given'
                );
            }
            if($options[1] === 0) {
                return 0;
            }
            if(\is_int($options[0]) === false) {
                throw new WidgetException(
                    'Failed loading widget data: Wrong type given'
                );
            }
            $billablePerc = \strval(round($options[0] / $options[1] * 100, 2)) . '%';

            return $billablePerc;
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
