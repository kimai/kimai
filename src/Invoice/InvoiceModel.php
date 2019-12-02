<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\Query\InvoiceQuery;

/**
 * InvoiceModel is the ONLY value that a RendererInterface receives for generating the invoice,
 * besides the InvoiceDocument which is used as a "template".
 */
final class InvoiceModel
{
    /**
     * @var Customer|null
     */
    private $customer;
    /**
     * @var InvoiceQuery
     */
    private $query;
    /**
     * @var InvoiceItemInterface[]
     */
    private $entries = [];
    /**
     * @var InvoiceTemplate
     */
    private $template;
    /**
     * @var CalculatorInterface
     */
    private $calculator;
    /**
     * @var NumberGeneratorInterface
     */
    private $generator;
    /**
     * @var \DateTime
     */
    private $invoiceDate;
    /**
     * @var User
     */
    private $user;
    /**
     * @var InvoiceFormatter
     */
    private $formatter;

    public function __construct(InvoiceFormatter $formatter)
    {
        $this->invoiceDate = new \DateTime();
        $this->formatter = $formatter;
    }

    /**
     * @return InvoiceQuery
     */
    public function getQuery(): ?InvoiceQuery
    {
        return $this->query;
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceModel
     */
    public function setQuery(InvoiceQuery $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Do not use this method for rendering the invoice, use InvoiceModel::getCalculator()->getEntries() instead.
     *
     * @return InvoiceItemInterface[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @deprecated since 1.3 - will be removed with 2.0
     * @param InvoiceItemInterface[] $entries
     * @return InvoiceModel
     */
    public function setEntries(array $entries): InvoiceModel
    {
        @trigger_error('setEntries() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        $this->entries = $entries;

        return $this;
    }

    /**
     * @param InvoiceItemInterface[] $entries
     * @return InvoiceModel
     */
    public function addEntries(array $entries): InvoiceModel
    {
        $this->entries = array_merge($this->entries, $entries);

        return $this;
    }

    public function getTemplate(): ?InvoiceTemplate
    {
        return $this->template;
    }

    public function setTemplate(InvoiceTemplate $template): InvoiceModel
    {
        $this->template = $template;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return InvoiceModel
     */
    public function setCustomer($customer): InvoiceModel
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        if (null === $this->getTemplate()) {
            return null;
        }

        return new \DateTime('+' . $this->getTemplate()->getDueDays() . ' days');
    }

    /**
     * @return \DateTime
     */
    public function getInvoiceDate(): \DateTime
    {
        return $this->invoiceDate;
    }

    public function setNumberGenerator(NumberGeneratorInterface $generator): InvoiceModel
    {
        $this->generator = $generator;
        $this->generator->setModel($this);

        return $this;
    }

    public function getNumberGenerator(): ?NumberGeneratorInterface
    {
        return $this->generator;
    }

    public function setCalculator(CalculatorInterface $calculator): InvoiceModel
    {
        $this->calculator = $calculator;
        $this->calculator->setModel($this);

        return $this;
    }

    public function getCalculator(): ?CalculatorInterface
    {
        return $this->calculator;
    }

    /**
     * Returns the user who is currently creating the invoice.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): InvoiceModel
    {
        $this->user = $user;

        return $this;
    }

    public function getFormatter(): ?InvoiceFormatter
    {
        return $this->formatter;
    }

    public function toArray(): array
    {
        $model = $this;
        $customer = $model->getCustomer();
        $project = $model->getQuery()->getProject();
        $activity = $model->getQuery()->getActivity();
        $currency = $model->getCalculator()->getCurrency();
        $tax = $model->getCalculator()->getTax();
        $total = $model->getCalculator()->getTotal();
        $subtotal = $model->getCalculator()->getSubtotal();
        $formatter = $model->getFormatter();

        $values = [
            'invoice.due_date' => $formatter->getFormattedDateTime($model->getDueDate()),
            'invoice.date' => $formatter->getFormattedDateTime($model->getInvoiceDate()),
            'invoice.number' => $model->getNumberGenerator()->getInvoiceNumber(),
            'invoice.currency' => $currency,
            'invoice.currency_symbol' => $formatter->getCurrencySymbol($currency),
            'invoice.vat' => $model->getCalculator()->getVat(),
            'invoice.tax' => $formatter->getFormattedMoney($tax, $currency),
            'invoice.tax_nc' => $formatter->getFormattedMoney($tax, null),
            'invoice.tax_plain' => $tax,
            'invoice.total_time' => $formatter->getFormattedDuration($model->getCalculator()->getTimeWorked()),
            'invoice.duration_decimal' => $formatter->getFormattedDecimalDuration($model->getCalculator()->getTimeWorked()),
            'invoice.total' => $formatter->getFormattedMoney($total, $currency),
            'invoice.total_nc' => $formatter->getFormattedMoney($total, null),
            'invoice.total_plain' => $total,
            'invoice.subtotal' => $formatter->getFormattedMoney($subtotal, $currency),
            'invoice.subtotal_nc' => $formatter->getFormattedMoney($subtotal, null),
            'invoice.subtotal_plain' => $subtotal,

            'template.name' => $model->getTemplate()->getName(),
            'template.company' => $model->getTemplate()->getCompany(),
            'template.address' => $model->getTemplate()->getAddress(),
            'template.title' => $model->getTemplate()->getTitle(),
            'template.payment_terms' => $model->getTemplate()->getPaymentTerms(),
            'template.due_days' => $model->getTemplate()->getDueDays(),
            'template.vat_id' => $model->getTemplate()->getVatId(),
            'template.contact' => $model->getTemplate()->getContact(),
            'template.payment_details' => $model->getTemplate()->getPaymentDetails(),

            'query.begin' => $formatter->getFormattedDateTime($model->getQuery()->getBegin()),
            'query.day' => $model->getQuery()->getBegin()->format('d'),
            'query.end' => $formatter->getFormattedDateTime($model->getQuery()->getEnd()),
            'query.month' => $formatter->getFormattedMonthName($model->getQuery()->getBegin()),
            'query.month_number' => $model->getQuery()->getBegin()->format('m'),
            'query.year' => $model->getQuery()->getBegin()->format('Y'),
        ];

        if (null !== $model->getUser()) {
            $user = $model->getUser();

            /** @var UserPreference $metaField */
            foreach ($user->getPreferences() as $metaField) {
                $values = array_merge($values, [
                    'user.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }

            $values = array_merge($values, [
                'user.name' => $user->getUsername(),
                'user.email' => $user->getEmail(),
                'user.title' => $user->getTitle(),
                'user.alias' => $user->getAlias(),
            ]);
        }

        if (null !== $activity) {
            $values = array_merge($values, [
                'activity.id' => $activity->getId(),
                'activity.name' => $activity->getName(),
                'activity.comment' => $activity->getComment(),
                'activity.fixed_rate' => $formatter->getFormattedMoney($activity->getFixedRate(), $currency),
                'activity.fixed_rate_nc' => $formatter->getFormattedMoney($activity->getFixedRate(), null),
                'activity.fixed_rate_plain' => $activity->getFixedRate(),
                'activity.hourly_rate' => $formatter->getFormattedMoney($activity->getHourlyRate(), $currency),
                'activity.hourly_rate_nc' => $formatter->getFormattedMoney($activity->getHourlyRate(), null),
                'activity.hourly_rate_plain' => $activity->getHourlyRate(),
            ]);

            foreach ($activity->getVisibleMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'activity.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        if (null !== $project) {
            $values = array_merge($values, [
                'project.id' => $project->getId(),
                'project.name' => $project->getName(),
                'project.comment' => $project->getComment(),
                'project.order_number' => $project->getOrderNumber(),
                'project.order_date' => null !== $project->getOrderDate() ? $formatter->getFormattedDateTime($project->getOrderDate()) : '',
                'project.fixed_rate' => $formatter->getFormattedMoney($project->getFixedRate(), $currency),
                'project.fixed_rate_nc' => $formatter->getFormattedMoney($project->getFixedRate(), null),
                'project.fixed_rate_plain' => $project->getFixedRate(),
                'project.hourly_rate' => $formatter->getFormattedMoney($project->getHourlyRate(), $currency),
                'project.hourly_rate_nc' => $formatter->getFormattedMoney($project->getHourlyRate(), null),
                'project.hourly_rate_plain' => $project->getHourlyRate(),
            ]);

            foreach ($project->getVisibleMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'project.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        if (null !== $customer) {
            $values = array_merge($values, [
                'customer.id' => $customer->getId(),
                'customer.address' => $customer->getAddress(),
                'customer.name' => $customer->getName(),
                'customer.contact' => $customer->getContact(),
                'customer.company' => $customer->getCompany(),
                'customer.vat' => $customer->getVatId(),
                'customer.number' => $customer->getNumber(),
                'customer.country' => $customer->getCountry(),
                'customer.homepage' => $customer->getHomepage(),
                'customer.comment' => $customer->getComment(),
                'customer.fixed_rate' => $formatter->getFormattedMoney($customer->getFixedRate(), $currency),
                'customer.fixed_rate_nc' => $formatter->getFormattedMoney($customer->getFixedRate(), null),
                'customer.fixed_rate_plain' => $customer->getFixedRate(),
                'customer.hourly_rate' => $formatter->getFormattedMoney($customer->getHourlyRate(), $currency),
                'customer.hourly_rate_nc' => $formatter->getFormattedMoney($customer->getHourlyRate(), null),
                'customer.hourly_rate_plain' => $customer->getHourlyRate(),
            ]);

            foreach ($customer->getVisibleMetaFields() as $metaField) {
                $values = array_merge($values, [
                    'customer.meta.' . $metaField->getName() => $metaField->getValue(),
                ]);
            }
        }

        return $values;
    }

    public function itemToArray(InvoiceItem $invoiceItem): array
    {
        $formatter = $this->getFormatter();

        $rate = $invoiceItem->getRate();
        $appliedRate = $invoiceItem->getHourlyRate();
        $amount = $formatter->getFormattedDuration($invoiceItem->getDuration());
        $description = $invoiceItem->getDescription();

        if ($invoiceItem->isFixedRate()) {
            $appliedRate = $invoiceItem->getFixedRate();
            $amount = $invoiceItem->getAmount();
        }

        if (empty($description)) {
            $description = $invoiceItem->getActivity()->getName();
        }

        $user = $invoiceItem->getUser();

        // this should never happen!
        if (empty($appliedRate)) {
            $appliedRate = 0;
        }

        $activity = $invoiceItem->getActivity();
        $project = $invoiceItem->getProject();
        $customer = $project->getCustomer();
        $currency = $customer->getCurrency();

        $begin = $invoiceItem->getBegin();
        $end = $invoiceItem->getEnd();

        $values = [
            'entry.row' => '',
            'entry.description' => $description,
            'entry.amount' => $amount,
            'entry.type' => $invoiceItem->getType(),
            'entry.category' => $invoiceItem->getCategory(),
            'entry.rate' => $formatter->getFormattedMoney($appliedRate, $currency),
            'entry.rate_nc' => $formatter->getFormattedMoney($appliedRate, null),
            'entry.rate_plain' => $appliedRate,
            'entry.total' => $formatter->getFormattedMoney($rate, $currency),
            'entry.total_nc' => $formatter->getFormattedMoney($rate, null),
            'entry.total_plain' => $rate,
            'entry.currency' => $currency,
            'entry.duration' => $invoiceItem->getDuration(),
            'entry.duration_decimal' => $formatter->getFormattedDecimalDuration($invoiceItem->getDuration()),
            'entry.duration_minutes' => number_format($invoiceItem->getDuration() / 60),
            'entry.begin' => $formatter->getFormattedDateTime($begin),
            'entry.begin_time' => $formatter->getFormattedTime($begin),
            'entry.begin_timestamp' => $begin->getTimestamp(),
            'entry.end' => $formatter->getFormattedDateTime($end),
            'entry.end_time' => $formatter->getFormattedTime($end),
            'entry.end_timestamp' => $end->getTimestamp(),
            'entry.date' => $formatter->getFormattedDateTime($begin),
            'entry.user_id' => $user->getId(),
            'entry.user_name' => $user->getUsername(),
            'entry.user_title' => $user->getTitle(),
            'entry.user_alias' => $user->getAlias(),
            'entry.activity' => $activity->getName(),
            'entry.activity_id' => $activity->getId(),
            'entry.project' => $project->getName(),
            'entry.project_id' => $project->getId(),
            'entry.customer' => $customer->getName(),
            'entry.customer_id' => $customer->getId(),
        ];

        foreach ($invoiceItem->getAdditionalFields() as $name => $value) {
            $values = array_merge($values, [
                'entry.meta.' . $name => $value,
            ]);
        }

        return $values;
    }
}
