<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Project;
use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'project.deleted', description: 'Triggered right before a project will be deleted', payload: 'object.getProject()')]
final class ProjectDeleteEvent extends AbstractProjectEvent
{
    public function __construct(Project $project, private readonly ?Project $replacementProject = null)
    {
        parent::__construct($project);
    }

    public function getReplacementProject(): ?Project
    {
        return $this->replacementProject;
    }
}
