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

final class ConfigurableNumberGenerator implements NumberGeneratorInterface
{
    /**
     * @var InvoiceModel
     */
    private $model;
    /**
     * @var InvoiceRepository
     */
    private $repository;
    /**
     * @var SystemConfiguration
     */
    private $configuration;

    public function __construct(InvoiceRepository $repository, SystemConfiguration $configuration)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'default';
    }

    /**
     * @param InvoiceModel $model
     */
    public function setModel(InvoiceModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber(): string
    {
        $format = $this->configuration->find('invoice.number_format');
        $invoiceDate = $this->model->getInvoiceDate();

        $loops = 0;
        $increaseBy = 0;

        do {
            $result = $format;

            preg_match_all('/{[^}]*?}/', $format, $matches);

            foreach ($matches[0] as $part) {
                $partialResult = $this->parseReplacer($invoiceDate, $part, $increaseBy);
                $result = str_replace($part, $partialResult, $result);
            }

            $increaseBy++;
        } while ($this->repository->hasInvoice($result) && $loops++ < 99);

        return (string) $result;
    }

    private function parseReplacer(\DateTime $invoiceDate, string $originalFormat, int $increaseBy): string
    {
        $formatterLength = null;
        $formatPattern = str_replace(['{', '}'], '', $originalFormat);

        $parts = preg_split('/([+\-,])+/', $formatPattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        $format = array_shift($parts);

        if (\count($parts) % 2 !== 0) {
            throw new \InvalidArgumentException('Invalid configuration found');
        }

        while (null !== ($tmp = array_shift($parts))) {
            switch ($tmp) {
                case '+':
                    $local = array_shift($parts);
                    if (!is_numeric($local)) {
                        throw new \InvalidArgumentException('Unknown increment found');
                    }
                    $increaseBy = $increaseBy + \intval($local);
                    break;

                case '-':
                    $local = array_shift($parts);
                    if (!is_numeric($local)) {
                        throw new \InvalidArgumentException('Unknown decrement found');
                    }
                    $increaseBy = $increaseBy - \intval($local);
                    break;

                case ',':
                    $local = array_shift($parts);
                    if (!is_numeric($local)) {
                        throw new \InvalidArgumentException('Unknown format length found');
                    }
                    $formatterLength = \intval($local);
                    if ((string) $formatterLength !== $local) {
                        throw new \InvalidArgumentException('Unknown format length found');
                    }
                    break;

                default:
                    throw new \InvalidArgumentException('Unknown pattern found');
            }
        }

        if ($increaseBy === 0) {
            $increaseBy = 1;
        }

        switch ($format) {
            case 'Y':
                $partialResult = $invoiceDate->format('Y');
                break;

            case 'y':
                $partialResult = $invoiceDate->format('y');
                break;

            case 'M':
                $partialResult = $invoiceDate->format('m');
                break;

            case 'm':
                $partialResult = $invoiceDate->format('n');
                break;

            case 'D':
                $partialResult = $invoiceDate->format('d');
                break;

            case 'd':
                $partialResult = $invoiceDate->format('j');
                break;

            case 'date':
                $partialResult = $invoiceDate->format('ymd');
                break;

            // for customer
            case 'cc':
                $partialResult = $this->repository->getCounterForCustomerAllTime($this->model->getCustomer()) + $increaseBy;
                break;

            case 'ccy':
                $partialResult = $this->repository->getCounterForYear($invoiceDate, $this->model->getCustomer()) + $increaseBy;
                break;

            case 'ccm':
                $partialResult = $this->repository->getCounterForMonth($invoiceDate, $this->model->getCustomer()) + $increaseBy;
                break;

            case 'ccd':
                $partialResult = $this->repository->getCounterForDay($invoiceDate, $this->model->getCustomer()) + $increaseBy;
                break;

            // for user
            case 'cu':
                $partialResult = $this->repository->getCounterForUserAllTime($this->model->getUser()) + $increaseBy;
                break;

            case 'cuy':
                $partialResult = $this->repository->getCounterForYear($invoiceDate, null, $this->model->getUser()) + $increaseBy;
                break;

            case 'cum':
                $partialResult = $this->repository->getCounterForMonth($invoiceDate, null, $this->model->getUser()) + $increaseBy;
                break;

            case 'cud':
                $partialResult = $this->repository->getCounterForDay($invoiceDate, null, $this->model->getUser()) + $increaseBy;
                break;

            case 'ustaff':
                $partialResult = $this->model->getUser() !== null ? $this->model->getUser()->getAccountNumber() : '';
                break;

            case 'uid':
                $partialResult = $this->model->getUser() !== null ? (string) $this->model->getUser()->getId() : '';
                break;

            // across all invoices
            case 'c':
                $partialResult = $this->repository->getCounterForCustomerAllTime() + $increaseBy;
                break;

            case 'cy':
                $partialResult = $this->repository->getCounterForYear($invoiceDate) + $increaseBy;
                break;

            case 'cm':
                $partialResult = $this->repository->getCounterForMonth($invoiceDate) + $increaseBy;
                break;

            case 'cd':
                $partialResult = $this->repository->getCounterForDay($invoiceDate) + $increaseBy;
                break;

            case 'cname':
                $partialResult = $this->model->getCustomer() !== null ? $this->model->getCustomer()->getName() : null;
                if ($partialResult === null) {
                    throw new \InvalidArgumentException('Customer has no name, replacer {cname} failed evaluation.');
                }
                break;

            case 'cnumber':
                $partialResult = $this->model->getCustomer() !== null ? $this->model->getCustomer()->getNumber() : null;
                if ($partialResult === null) {
                    throw new \InvalidArgumentException('Customer has no number, replacer {cnumber} failed evaluation.');
                }
                break;

            default:
                $partialResult = $originalFormat;
        }

        if (null !== $formatterLength) {
            $partialResult = str_pad($partialResult, $formatterLength, '0', STR_PAD_LEFT);
        }

        return $partialResult;
    }
}
