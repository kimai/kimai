<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

abstract class AbstractCounterYear extends AbstractWidgetType
{
    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $begin = $this->createDate('01 january this year 00:00:00');
        $end = $this->createDate('31 december this year 23:59:59');

        return $this->getYearData($begin, $end, $options);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    abstract protected function getYearData(\DateTimeInterface $begin, \DateTimeInterface $end, array $options = []): mixed;

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
