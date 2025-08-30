<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Model\PermissionSectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can be used, to dynamically add sections to the permission screen.
 */
final class PermissionSectionsEvent extends Event
{
    /**
     * @var array<PermissionSectionInterface>
     */
    private array $sections = []; // @phpstan-ignore property.deprecatedInterface

    public function addSection(PermissionSectionInterface $section): PermissionSectionsEvent // @phpstan-ignore parameter.deprecatedInterface
    {
        $this->sections[] = $section;

        return $this;
    }

    /**
     * @return PermissionSectionInterface[]
     */
    public function getSections(): array  // @phpstan-ignore return.deprecatedInterface
    {
        return $this->sections;
    }
}
