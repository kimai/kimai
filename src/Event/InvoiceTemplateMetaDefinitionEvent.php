<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\InvoiceTemplate;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can be used, to dynamically add meta fields to invoice templates
 */
final class InvoiceTemplateMetaDefinitionEvent extends Event
{
    public function __construct(private readonly InvoiceTemplate $entity)
    {
    }

    public function getEntity(): InvoiceTemplate
    {
        return $this->entity;
    }
}
