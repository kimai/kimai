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
     * @var string
     */
    private $format;

    public function __construct(InvoiceRepository $repository, SystemConfiguration $configuration)
    {
        $this->repository = $repository;
        $this->format = $configuration->find('invoice.number_format');
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
        $format = $this->format;
        $invoiceDate = $this->model->getInvoiceDate();
        $timestamp = $invoiceDate->getTimestamp();
        $result = $format;

        preg_match_all('/{[^}]*?}/', $format, $matches);
        foreach ($matches[0] as $part) {
            $formatter = null;
            $tmp = str_replace(['{', '}'], '', $part);

            // number format
            if (substr_count($tmp, ',') !== 0) {
                $formatter = explode(',', $tmp);
                $tmp = $formatter[0];
                $formatter = $formatter[1];
            }

            switch ($tmp) {
                case 'Y':
                    $partialResult = date('Y', $timestamp);
                    break;

                case 'y':
                    $partialResult = date('y', $timestamp);
                    break;

                case 'M':
                    $partialResult = date('m', $timestamp);
                    break;

                case 'm':
                    $partialResult = date('n', $timestamp);
                    break;

                case 'D':
                    $partialResult = date('d', $timestamp);
                    break;

                case 'd':
                    $partialResult = date('j', $timestamp);
                    break;

                case 'date':
                    $partialResult = date('ymd', $timestamp);
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
