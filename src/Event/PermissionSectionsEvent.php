<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Model\PermissionSection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows to dynamically add sections to the permission screen.
 */
final class PermissionSectionsEvent extends Event
{
    /**
     * @var array<PermissionSection>
     */
    private array $sections = [];

    public function addSection(PermissionSection $section): void
    {
        $this->sections[] = $section;
    }

    /**
     * @return PermissionSection[]
     */
    public function getSections(): array
    {
        return $this->sections;
    }
}
