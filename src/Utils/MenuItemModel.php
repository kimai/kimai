<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use KevinPapst\TablerBundle\Model\MenuItemInterface;

final class MenuItemModel implements MenuItemInterface
{
    private string $identifier;
    private string $label;
    private ?string $route;
    private array $routeArgs;
    private bool $isActive = false;
    /** @var array<MenuItemModel> */
    private array $children = [];
    private ?string $icon;
    private ?MenuItemModel $parent = null;
    private ?string $badge = null;
    private ?string $badgeColor = null;
    private static int $dividerId = 0;
    private bool $divider = false;
    private bool $lastWasDivider = false;
    private bool $expanded = false;

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

    /**
     * @return MenuItemModel[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getChild(string $id): ?MenuItemModel
    {
        foreach ($this->children as $child) {
            if ($child->getIdentifier() === $id) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @param array<MenuItemModel> $children
     */
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
        $this->getParent()?->setIsActive($isActive);

        $this->isActive = $isActive;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getParent(): ?MenuItemModel
    {
        return $this->parent;
    }

    public function setParent(MenuItemInterface $parent): void
    {
        if (!($parent instanceof MenuItemModel)) {
            throw new \Exception('MenuItemModel::setParent() expects a MenuItemModel');
        }
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
        if (!($child instanceof MenuItemModel)) {
            throw new \Exception('MenuItemModel::addChild() expects a MenuItemModel');
        }

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

    public function findChild(string $identifier): ?MenuItemModel
    {
        return $this->find($identifier, $this);
    }

    private function find(string $identifier, MenuItemModel $menu): ?MenuItemModel
    {
        if ($menu->getIdentifier() === $identifier) {
            return $this;
        }

        foreach ($menu->getChildren() as $child) {
            if ($child->getIdentifier() === $identifier) {
                return $child;
            }
            if ($child->hasChildren()) {
                if (($tmp = $this->find($identifier, $child)) !== null) {
                    return $tmp;
                }
            }
        }

        return null;
    }

    public function getActiveChild(): ?MenuItemModel
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

    private array $childRoutes = [];

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

    public function isExpanded(): bool
    {
        return $this->expanded;
    }

    public function setExpanded(bool $expanded): void
    {
        $this->expanded = $expanded;
    }
}
