<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Invoice\InvoiceItem;
use App\Invoice\InvoiceItemHydrator;
use App\Invoice\InvoiceModel;

final class InvoiceItemDefaultHydrator implements InvoiceItemHydrator
{
    private const DATE_PROCESS_FORMAT = 'Y-m-d h:i:s';

    private InvoiceModel $model;

    public function setInvoiceModel(InvoiceModel $model): void
    {
        $this->model = $model;
    }

    public function hydrate(InvoiceItem $item): array
    {
        $formatter = $this->model->getFormatter();

        $rate = $item->getRate();
        $internalRate = $item->getInternalRate();
        $appliedRate = $item->getHourlyRate();
        $amount = $formatter->getFormattedDecimalDuration($item->getDuration());
        $description = $item->getDescription();

        if ($item->isFixedRate()) {
            $appliedRate = $item->getFixedRate();
            $amount = $formatter->getFormattedAmount($item->getAmount());
        }

        $activity = $item->getActivity();
        $project = $item->getProject();
        $customer = $project->getCustomer();
        $currency = $customer->getCurrency();
        $user = $item->getUser();
        $begin = $item->getBegin();
        $end = $item->getEnd();

        // this should never happen!
        if (empty($appliedRate)) {
            $appliedRate = 0;
        }

        $values = [
            'entry.row' => '',
            'entry.description' => $description ?? '',
            'entry.description_safe' => ($description === null || $description === '' ? ($activity?->getName() ?? $project?->getName() ?? '') : $description),
            'entry.amount' => $amount,
            'entry.type' => $item->getType(),
            'entry.tags' => implode(', ', $item->getTags()),
            'entry.category' => $item->getCategory() ?? '',
            'entry.rate' => $formatter->getFormattedMoney($appliedRate, $currency),
            'entry.rate_nc' => $formatter->getFormattedMoney($appliedRate, $currency, false),
            'entry.rate_plain' => $appliedRate,
            'entry.rate_internal' => $formatter->getFormattedMoney($internalRate, $currency),
            'entry.rate_internal_nc' => $formatter->getFormattedMoney($internalRate, $currency, false),
            'entry.rate_internal_plain' => $internalRate,
            'entry.total' => $formatter->getFormattedMoney($rate, $currency),
            'entry.total_nc' => $formatter->getFormattedMoney($rate, $currency, false),
            'entry.total_plain' => $rate,
            'entry.currency' => $currency,
            'entry.duration' => $item->getDuration(),
            'entry.duration_format' => $formatter->getFormattedDuration($item->getDuration()),
            'entry.duration_decimal' => $formatter->getFormattedDecimalDuration($item->getDuration()),
            'entry.duration_minutes' => (int) ($item->getDuration() / 60),
        ];

        if ($begin !== null) {
            $values['entry.begin'] = $formatter->getFormattedDateTime($begin);
            $values['entry.begin_time'] = $formatter->getFormattedTime($begin);
            $values['entry.begin_timestamp'] = $begin->getTimestamp();
            $values['entry.date'] = $formatter->getFormattedDateTime($begin);
            $values['entry.date_process'] = $begin->format(self::DATE_PROCESS_FORMAT); // since 2.14
            $values['entry.week'] = \intval($begin->format('W'));
            $values['entry.weekyear'] = $begin->format('o');
        }

        if ($end !== null) {
            $values['entry.end'] = $formatter->getFormattedDateTime($end);
            $values['entry.end_time'] = $formatter->getFormattedTime($end);
            $values['entry.end_timestamp'] = $end->getTimestamp();
        }

        if (null !== $user) {
            $values = array_merge($values, [
                'entry.user_id' => $user->getId(),
                'entry.user_name' => $user->getUserIdentifier(),
                'entry.user_title' => $user->getTitle() ?? '',
                'entry.user_alias' => $user->getAlias() ?? '',
                'entry.user_display' => $user->getDisplayName(),
            ]);

            foreach ($user->getVisiblePreferences() as $pref) {
                $values['entry.user_preference.' . $pref->getName()] = $pref->getValue();
            }
        }

        if (null !== $activity) {
            $values = array_merge($values, [
                'entry.activity' => $activity->getName(),
                'entry.activity_id' => $activity->getId(),
            ]);

            foreach ($activity->getMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'entry.activity.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        if (null !== $project) {
            $values = array_merge($values, [
                'entry.project' => $project->getName(),
                'entry.project_id' => $project->getId(),
            ]);

            foreach ($project->getMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'entry.project.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        if (null !== $customer) {
            $values = array_merge($values, [
                'entry.customer' => $customer->getName(),
                'entry.customer_id' => $customer->getId(),
            ]);

            foreach ($customer->getMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'entry.customer.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        foreach ($item->getAdditionalFields() as $name => $value) {
            $values = array_merge($values, [
                'entry.meta.' . $name => $value,
            ]);
        }

        return $values;
    }
}
