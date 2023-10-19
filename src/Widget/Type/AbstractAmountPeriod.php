<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Event\RevenueStatisticEvent;
use App\Repository\TimesheetRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractAmountPeriod extends AbstractWidget
{
    public function __construct(private TimesheetRepository $repository, private EventDispatcherInterface $dispatcher)
    {
    }

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-money.html.twig';
    }

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

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, float>
     */
    protected function getRevenue(?\DateTimeInterface $begin, ?\DateTimeInterface $end, array $options = []): array
    {
        $data = $this->repository->getRevenue($begin, $end, null);

        $event = new RevenueStatisticEvent($begin, $end);
        foreach ($data as $row) {
            $event->addRevenue($row->getCurrency(), $row->getAmount());
        }
        $this->dispatcher->dispatch($event);

        return $event->getRevenue();
    }
}
