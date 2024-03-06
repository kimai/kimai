<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

class CustomerQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

    public const CUSTOMER_ORDER_ALLOWED = [
        'name', 'description' => 'comment', 'country', 'number', 'homepage', 'email', 'mobile', 'fax',
        'phone', 'currency', 'address', 'contact', 'company', 'vat_id', 'budget', 'timeBudget', 'visible'
    ];

    private ?string $country = null;

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
            'country' => null,
        ]);
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }
}
