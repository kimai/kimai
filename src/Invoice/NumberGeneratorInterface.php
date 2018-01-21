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
 * Class NumberGeneratorInterface defines all methods that invoice number generator have to implement.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
interface NumberGeneratorInterface
{

    /**
     * @param InvoiceModel $model
     */
    public function setModel(InvoiceModel $model);

    /**
     * @return string
     */
    public function getInvoiceNumber(): string;
}
