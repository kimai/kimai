<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Project;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can be used, to dynamically add meta fields to projects
 */
final class ProjectMetaDefinitionEvent extends Event
{
    /**
     * @var Project
     */
    private $entity;

    public function __construct(Project $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): Project
    {
        return $this->entity;
    }
}
