<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Model\InvoiceModel;

/**
 * CalculatorInterface defines all methods for any invoice price calculator.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
interface CalculatorInterface
{

    /**
     * @param InvoiceModel $model
     */
    public function setModel(InvoiceModel $model);

    /**
     * @return float
     */
    public function getSubtotal(): float;

    /**
     * @return float
     */
    public function getTax(): float;

    /**
     * @return float
     */
    public function getTotal(): float;

    /**
     * @return string
     */
    public function getCurrency(): string;
}
