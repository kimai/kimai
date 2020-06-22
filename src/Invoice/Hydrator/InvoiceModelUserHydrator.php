<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Entity\UserPreference;
use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

class InvoiceModelUserHydrator implements InvoiceModelHydrator
{
    public function hydrate(InvoiceModel $model): array
    {
        $user = $model->getUser();

        if (null === $user) {
            return [];
        }

        $values = [
            'user.name' => $user->getUsername(),
            'user.email' => $user->getEmail(),
            'user.title' => $user->getTitle(),
            'user.alias' => $user->getAlias(),
        ];

        /** @var UserPreference $metaField */
        foreach ($user->getPreferences() as $metaField) {
            $values = array_merge($values, [
                'user.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}
