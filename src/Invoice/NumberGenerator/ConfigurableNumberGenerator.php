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
            $formatter = null;
            $tmp = str_replace(['{', '}'], '', $part);

            // number format
            if (substr_count($tmp, ',') !== 0) {
                $parts = explode(',', $tmp);
                $tmp = $parts[0];
                $formatter = \intval($parts[1]);
                if ((string) $formatter !== $parts[1]) {
                    $formatter = null;
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

                case 'c':
                    $partialResult = $this->repository->getCounterForAllTime($invoiceDate) + 1;
                    break;

                case 'cy':
                    $partialResult = $this->repository->getCounterForYear($invoiceDate) + 1;
                    break;

                case 'cm':
                    $partialResult = $this->repository->getCounterForMonth($invoiceDate) + 1;
                    break;

                case 'cd':
                    $partialResult = $this->repository->getCounterForDay($invoiceDate) + 1;
                    break;

                default:
                    $partialResult = $part;
            }

            if (null !== $formatter) {
                $partialResult = str_pad($partialResult, $formatter, '0', STR_PAD_LEFT);
            }

            $result = str_replace($part, $partialResult, $result);
        }

        return (string) $result;
    }
}
