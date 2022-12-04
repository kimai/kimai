<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\NumberGenerator;

use App\Configuration\SystemConfiguration;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGeneratorInterface;
use App\Repository\InvoiceRepository;
use App\Utils\NumberGenerator;

final class ConfigurableNumberGenerator implements NumberGeneratorInterface
{
    private ?InvoiceModel $model = null;

    public function __construct(private InvoiceRepository $repository, private SystemConfiguration $configuration)
    {
    }

    public function getId(): string
    {
        return 'default';
    }

    public function setModel(InvoiceModel $model): void
    {
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber(): string
    {
        $format = $this->configuration->find('invoice.number_format');
        if (empty($format) || !\is_string($format)) {
            $format = '{Y}/{cy,3}';
        }

        $invoiceDate = $this->model->getInvoiceDate();

        $loops = 0;
        $increaseBy = 0;

        $numberGenerator = new NumberGenerator($format, function (string $originalFormat, string $format, int $increaseBy) use ($invoiceDate): string|int {
            if ($this->model === null) {
                throw new \InvalidArgumentException('Missing invoice model, cannot calculate invoice number');
            }

            return match ($format) {
                'Y' => $invoiceDate->format('Y'),
                'y' => $invoiceDate->format('y'),
                'M' => $invoiceDate->format('m'),
                'm' => $invoiceDate->format('n'),
                'D' => $invoiceDate->format('d'),
                'd' => $invoiceDate->format('j'),
                'date' => $invoiceDate->format('ymd'),
                'cc' => $this->repository->getCounterForCustomerAllTime($this->model->getCustomer()) + $increaseBy,
                'ccy' => $this->repository->getCounterForYear($invoiceDate, $this->model->getCustomer()) + $increaseBy,
                'ccm' => $this->repository->getCounterForMonth($invoiceDate, $this->model->getCustomer()) + $increaseBy,
                'ccd' => $this->repository->getCounterForDay($invoiceDate, $this->model->getCustomer()) + $increaseBy,
                'cu' => $this->repository->getCounterForUserAllTime($this->model->getUser()) + $increaseBy,
                'cuy' => $this->repository->getCounterForYear($invoiceDate, null, $this->model->getUser()) + $increaseBy,
                'cum' => $this->repository->getCounterForMonth($invoiceDate, null, $this->model->getUser()) + $increaseBy,
                'cud' => $this->repository->getCounterForDay($invoiceDate, null, $this->model->getUser()) + $increaseBy,
                'ustaff' => (string) $this->model->getUser()?->getAccountNumber(),
                'uid' => (string) $this->model->getUser()?->getId(),
                'c' => $this->repository->getCounterForCustomerAllTime() + $increaseBy,
                'cy' => $this->repository->getCounterForYear($invoiceDate) + $increaseBy,
                'cm' => $this->repository->getCounterForMonth($invoiceDate) + $increaseBy,
                'cd' => $this->repository->getCounterForDay($invoiceDate) + $increaseBy,
                'cname' => (string) $this->model->getCustomer()?->getName(),
                'cnumber' => (string) $this->model->getCustomer()?->getNumber(),
                default => $originalFormat,
            };
        });

        do {
            $result = $numberGenerator->getNumber($increaseBy);
            $increaseBy++;
        } while ($this->repository->hasInvoice($result) && $loops++ < 99);

        return $result;
    }
}
