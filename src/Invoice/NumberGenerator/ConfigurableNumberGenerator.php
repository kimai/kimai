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
        $result = $format;

        preg_match_all('/{[^}]*?}/', $format, $matches);
        foreach ($matches[0] as $part) {
            $formatterLength = null;
            $increaseBy = 1;

            $tmp = str_replace(['{', '}'], '', $part);

            $parts = preg_split('/[,]+/', $tmp);
            $tmp = $parts[0];
            if (\count($parts) === 2) {
                $formatterLength = \intval($parts[1]);
                if ((string) $formatterLength !== $parts[1]) {
                    $formatterLength = null;
                }
            }

            $parts = preg_split("/[\+]+/", $tmp);
            $tmp = $parts[0];
            if (\count($parts) === 2) {
                $increaseBy = \intval($parts[1]);
                if ($increaseBy <= 0) {
                    $increaseBy = 1;
                }
            }

            switch ($tmp) {
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
                    $partialResult = $this->repository->getCounterForAllTime($invoiceDate, $this->model->getCustomer()) + 1;
                    break;

                case 'ccy':
                    $partialResult = $this->repository->getCounterForYear($invoiceDate, $this->model->getCustomer()) + 1;
                    break;

                case 'ccm':
                    $partialResult = $this->repository->getCounterForMonth($invoiceDate, $this->model->getCustomer()) + 1;
                    break;

                case 'ccd':
                    $partialResult = $this->repository->getCounterForDay($invoiceDate, $this->model->getCustomer()) + 1;
                    break;

                // across all invoices
                case 'c':
                    $partialResult = $this->repository->getCounterForAllTime($invoiceDate) + $increaseBy;
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

                default:
                    $partialResult = $part;
            }

            if (null !== $formatterLength) {
                $partialResult = str_pad($partialResult, $formatterLength, '0', STR_PAD_LEFT);
            }

            $result = str_replace($part, $partialResult, $result);
        }

        return (string) $result;
    }
}
