<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_invoice_templates_meta')]
#[ORM\UniqueConstraint(columns: ['template_id', 'name'])]
#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
class InvoiceTemplateMeta implements MetaTableTypeInterface
{
    use MetaTableTypeTrait;

    #[ORM\ManyToOne(targetEntity: InvoiceTemplate::class, inversedBy: 'meta')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?InvoiceTemplate $template = null;

    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface
    {
        if (!($entity instanceof InvoiceTemplate)) {
            throw new \InvalidArgumentException(
                \sprintf('Expected instanceof InvoiceTemplate, received "%s"', \get_class($entity))
            );
        }
        $this->template = $entity;

        return $this;
    }

    /**
     * @return InvoiceTemplate|null
     */
    public function getEntity(): ?EntityWithMetaFields
    {
        return $this->template;
    }
}
