<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerQuery;

final class TotalsCustomer extends SimpleWidget implements UserWidget, AuthorizedWidget
{
    use UserWidgetTrait;

    private $customer;

    public function __construct(CustomerRepository $customer)
    {
        $this->customer = $customer;
        $this->setTitle('stats.customerTotal');
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'admin_customer',
            'icon' => 'customer',
            'color' => 'primary',
            'dataType' => 'int',
        ], parent::getOptions($options));
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $user = $options['user'];
        if (null === $user || !($user instanceof User)) {
            throw new \InvalidArgumentException('Widget option "user" must be an instance of ' . User::class);
        }

        $query = new CustomerQuery();
        $query->setCurrentUser($user);

        return $this->customer->countCustomersForQuery($query);
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return ['view_customer', 'view_teamlead_customer', 'view_team_customer'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }
}
