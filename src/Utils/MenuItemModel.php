<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use KevinPapst\TablerBundle\Model\MenuItemInterface;

class MenuItemModel implements MenuItemInterface
{
    private string $identifier;
    private string $label;
    private ?string $route;
    private array $routeArgs;
    private bool $isActive = false;
    /**
     * @var array<MenuItemInterface>
     */
    private array $children = [];
    private ?string $icon;
    private ?MenuItemInterface $parent = null;
    private ?string $badge;
    private ?string $badgeColor;
    private static $dividerId = 0;
    private bool $divider = false;
    private bool $lastWasDivider = false;

    public function __construct(
        string $id,
        string $label,
        ?string $route = null,
        array $routeArgs = [],
        ?string $icon = null
    ) {
        $this->identifier = $id;
        $this->label = $label;
        $this->route = $route;
        $this->routeArgs = $routeArgs;
        $this->icon = $icon;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getChild(string $id): ?MenuItemInterface
    {
        foreach ($this->children as $child) {
            if ($child->getIdentifier() === $id) {
                return $child;
            }
        }

        return null;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        if ($this->hasParent()) {
            $parent = $this->getParent();
            if ($parent instanceof MenuItemModel) {
                $parent->setIsActive($isActive);
            }
        }

        $this->isActive = $isActive;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getParent(): ?MenuItemInterface
    {
        return $this->parent;
    }

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): void
    {
        $this->route = $route;
    }

    public function getRouteArgs(): array
    {
        return $this->routeArgs;
    }

    public function setRouteArgs(array $routeArgs): void
    {
        $this->routeArgs = $routeArgs;
    }

    public function hasChildren(): bool
    {
        if (\count($this->children) < 1) {
            return false;
        }

        foreach ($this->children as $child) {
            if (!$child->isDivider()) {
                return true;
            }
        }

        return false;
    }

    public function addChild(MenuItemInterface $child): void
    {
        // first item should not be a divider
        if (!$this->hasChildren() && $child->isDivider()) {
            return;
        }

        // two divider should not be added as direct siblings
        if ($this->lastWasDivider && $child->isDivider()) {
            return;
        }
        $this->lastWasDivider = $child->isDivider();

        $child->setParent($this);
        $this->children[] = $child;
    }

    public function removeChild(MenuItemInterface $child): void
    {
        if (false !== ($key = array_search($child, $this->children))) {
            unset($this->children[$key]);
        }
    }

    public function getActiveChild(): ?MenuItemInterface
    {
        foreach ($this->children as $child) {
            if ($child->isActive()) {
                return $child;
            }
        }

        return null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setBadge(?string $badge): void
    {
        $this->badge = $badge;
    }

    public function setBadgeColor(?string $badgeColor): void
    {
        $this->badgeColor = $badgeColor;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getBadgeColor(): ?string
    {
        return $this->badgeColor;
    }

    private $childRoutes = [];

    public function setChildRoutes(array $routes): MenuItemModel
    {
        $this->childRoutes = $routes;

        return $this;
    }

    public function addChildRoute(string $route): MenuItemModel
    {
        $this->childRoutes[] = $route;

        return $this;
    }

    public function isChildRoute(string $route): bool
    {
        return \in_array($route, $this->childRoutes);
    }

    public static function createDivider(): MenuItemModel
    {
        $model = new MenuItemModel('divider_' . self::$dividerId++, '');
        $model->setDivider(true);

        return $model;
    }

    public function isDivider(): bool
    {
        return $this->divider;
    }

    public function setDivider(bool $divider): void
    {
        $this->divider = $divider;
    }
}
