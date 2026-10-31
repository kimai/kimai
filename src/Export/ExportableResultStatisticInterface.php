<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Customer;

interface ExportableResultStatisticInterface
{
    public function getCustomer(): Customer;

    /**
     * @return non-negative-int
     */
    public function getCount(): int;

    public function getDuration(): int;

    public function getRate(): float;
}
