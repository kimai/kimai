<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Event\RevenueStatisticEvent;
use App\Model\Revenue;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class AmountYear extends AbstractCounterYear
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        SystemConfiguration $systemConfiguration,
        private readonly EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($systemConfiguration);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_YEAR,
        ], parent::getOptions($options));
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, float>
     */
    protected function getYearData(\DateTimeInterface $begin, \DateTimeInterface $end, array $options = []): array
    {
        try {
            /** @var array<Revenue> $data */
            $data = $this->repository->getRevenue($begin, $end, null);

            $event = new RevenueStatisticEvent($begin, $end);
            foreach ($data as $row) {
                $event->addRevenue($row->getCurrency(), $row->getAmount());
            }
            $this->dispatcher->dispatch($event);

            return $event->getRevenue();
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }

    public function getId(): string
    {
        return 'AmountYear';
    }

    protected function getFinancialYearTitle(): string
    {
        return 'stats.amountFinancialYear';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-money.html.twig';
    }

    public function getPermissions(): array
    {
        return ['view_all_data'];
    }
}
