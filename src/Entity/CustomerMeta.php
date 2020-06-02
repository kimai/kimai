<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="kimai2_customers_meta",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"customer_id", "name"})
 *      }
 * )
 */
class CustomerMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="meta")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $customer;

    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface
    {
        if (!($entity instanceof Customer)) {
            throw new \InvalidArgumentException(
                sprintf('Expected instanceof Customer, received "%s"', \get_class($entity))
            );
        }
        $this->customer = $entity;

        return $this;
    }

    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->customer;
    }
}
