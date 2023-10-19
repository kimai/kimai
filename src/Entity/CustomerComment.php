<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_customers_comments')]
#[ORM\Index(columns: ['customer_id'])]
#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class CustomerComment implements CommentInterface
{
    use CommentTableTypeTrait;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Customer $customer;

    public function __construct(Customer $customer)
    {
        $this->createdAt = new \DateTime();
        $this->customer = $customer;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }
}
