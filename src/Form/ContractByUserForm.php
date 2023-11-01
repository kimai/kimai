<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;

/**
 * @internal
 */
final class ContractByUserForm extends AbstractType
{
    public function getParent(): string
    {
        return YearByUserForm::class;
    }
}
