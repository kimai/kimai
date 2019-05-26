<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

class TimesheetAdminEditForm extends TimesheetEditForm
{
    protected function showTimeFields(array $options): bool
    {
        return true;
    }

    protected function showCustomer(array $options, bool $isNew, int $customerCount): bool
    {
        return true;
    }
}
