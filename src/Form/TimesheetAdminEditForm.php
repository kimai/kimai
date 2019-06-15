<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;

class TimesheetAdminEditForm extends TimesheetEditForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['allow_begin_datetime'] = true;
        $options['allow_end_datetime'] = true;
        $options['allow_duration'] = false;

        parent::buildForm($builder, $options);
    }

    protected function showCustomer(array $options, bool $isNew, int $customerCount): bool
    {
        return true;
    }
}
