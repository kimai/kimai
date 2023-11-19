<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;

/**
 * This event is triggered for every action:
 * - once per side load for page actions
 * - once for every entity item (table row)
 *
 * @property array{'actions': array, 'view': string} $payload
 */
class PageActionsEvent extends ThemeEvent
{
    private int $divider = 0;
    private ?string $locale = null;

    public function __construct(User $user, array $payload, private string $action, private string $view)
    {
        // only for BC reasons, do not access it directly!
        if (!\array_key_exists('actions', $payload)) {
            $payload['actions'] = [];
        }
        // only for BC reasons, do not access it directly!
        if (!\array_key_exists('view', $payload)) {
            $payload['view'] = $view;
        }
        parent::__construct($user, $payload);
    }

    public function getActionName(): string
    {
        return $this->action;
    }

    public function getEventName(): string
    {
        return 'actions.' . $this->getActionName();
    }

    public function isView(string $view): bool
    {
        return $this->view === $view;
    }

    public function isIndexView(): bool
    {
        return $this->isView('index');
    }

    /**
     * Custom view can only be table listings.
     *
     * @return bool
     */
    public function isCustomView(): bool
    {
        return $this->isView('custom');
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getActions(): array
    {
        $actions = $this->payload['actions'];

        // move documentation to end of list
        if (\array_key_exists('help', $actions)) {
            $help = $actions['help'];
            unset($actions['help']);
            $actions += ['help' => $help];
        }

        // move trash to end of list
        if (\array_key_exists('trash', $actions)) {
            $delete = $actions['trash'];
            unset($actions['trash']);
            $actions += ['trash' => $delete];
        }

        return $actions;
    }

    public function hasAction(string $key): bool
    {
        return \array_key_exists($key, $this->payload['actions']);
    }

    public function hasSubmenu(string $submenu): bool
    {
        if (!$this->hasAction($submenu)) {
            return false;
        }

        return \array_key_exists('children', $this->payload['actions'][$submenu]);
    }

    public function addActionToSubmenu(string $submenu, string $key, array $action): void
    {
        if ($this->hasAction($submenu)) {
            if (!\array_key_exists('children', $this->payload['actions'][$submenu])) {
                $this->payload['actions'][$submenu]['children'] = [];
            }
        }
        $this->payload['actions'][$submenu]['children'][$key] = $action;
    }

    public function replaceAction(string $key, array $action): void
    {
        $this->payload['actions'][$key] = $action;
    }

    public function addAction(string $key, array $action): void
    {
        if (!$this->hasAction($key)) {
            $this->payload['actions'][$key] = $action;
        }
    }

    public function removeAction(string $key): void
    {
        if ($this->hasAction($key)) {
            unset($this->payload['actions'][$key]);
        }
    }

    public function addDivider(): void
    {
        $key = 'divider' . $this->divider++;
        $this->payload['actions'][$key] = null;
    }

    public function addQuickExport(string $url): void
    {
        $this->addAction('download', ['url' => $url, 'class' => 'toolbar-action', 'title' => 'export']);
    }

    public function addCreate(string $url, bool $modal = true): void
    {
        $this->addAction('create', ['url' => $url, 'class' => ($modal ? 'modal-ajax-form' : ''), 'title' => 'create', 'accesskey' => 'a']);
    }

    public function addEdit(string $url, bool $modal = true, string $class = ''): void
    {
        $this->addAction('edit', ['url' => $url, 'class' => ($modal ? 'modal-ajax-form' . ($class === '' ? '' : ' ' . $class) : $class), 'translation_domain' => 'actions', 'title' => 'edit']);
    }

    /**
     * Link to a configuration section.
     */
    public function addSettings(string $url): void
    {
        $this->addAction('settings', ['url' => $url, 'class' => 'modal-ajax-form', 'title' => 'settings', 'translation_domain' => 'actions', 'accesskey' => 'h']);
    }

    public function addConfig(string $url): void
    {
        $this->addAction('settings', ['url' => $url, 'title' => 'settings', 'translation_domain' => 'actions']);
    }

    public function addDelete(string $url, bool $remoteConfirm = true): void
    {
        if ($remoteConfirm) {
            $this->addAction('trash', ['url' => $url, 'class' => 'modal-ajax-form text-red', 'translation_domain' => 'actions', 'title' => 'trash']);
        } else {
            $this->addAction('trash', ['url' => $url, 'class' => 'confirmation-link text-red', 'attr' => ['data-question' => 'confirm.delete'], 'translation_domain' => 'actions', 'title' => 'trash']);
        }
    }

    public function addColumnToggle(string $modal): void
    {
        $modal = '#' . ltrim($modal, '#');
        $this->addAction('columns', ['modal' => $modal, 'title' => 'modal.columns.title']);
    }

    public function countActions(?string $submenu = null): int
    {
        if ($submenu !== null) {
            if (!$this->hasSubmenu($submenu)) {
                return 0;
            }

            return \count($this->payload['actions'][$submenu]['children']);
        }

        return \count($this->payload['actions']);
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }
}
