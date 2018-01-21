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
 * Class RandomNumberGenerator is meant for testing purpose only.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class RandomNumberGenerator implements NumberGeneratorInterface
{
    /**
     * @var InvoiceModel
     */
    protected $model;

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
        return rand(1000000, 9999999);
    }
}
