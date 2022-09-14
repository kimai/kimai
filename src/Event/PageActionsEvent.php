<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\Repository\Query\BaseQuery;

/**
 * This event is triggered once per side load.
 * It stores all toolbar items, which should be rendered in the upper right corner.
 */
class PageActionsEvent extends ThemeEvent
{
    private $action;
    private $view;
    private $divider = 0;

    public function __construct(User $user, array $payload, string $action, string $view)
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
        $this->action = $action;
        $this->view = $view;
    }

    public function getActionName(): string
    {
        return $this->action;
    }

    public function isView(string $view): bool
    {
        return $this->view === $view;
    }

    public function isIndexView(): bool
    {
        return $this->isView('index');
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
        if (!\array_key_exists($submenu, $this->payload['actions'])) {
            return false;
        }

        return \array_key_exists('children', $this->payload['actions'][$submenu]);
    }

    public function addActionToSubmenu(string $submenu, string $key, array $action): void
    {
        if (\array_key_exists($submenu, $this->payload['actions'])) {
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
        if (!\array_key_exists($key, $this->payload['actions'])) {
            $this->payload['actions'][$key] = $action;
        }
    }

    public function removeAction(string $key): void
    {
        if (\array_key_exists($key, $this->payload['actions'])) {
            unset($this->payload['actions'][$key]);
        }
    }

    public function addDivider(): void
    {
        $key = 'divider' . $this->divider++;
        $this->payload['actions'][$key] = null;
    }

    public function addSearchToggle(?BaseQuery $query = null): void
    {
        $label = null;

        if ($query !== null) {
            $label = $query->countFilter();
            if ($label < 1) {
                $label = null;
            }
        }

        $this->addAction('search', ['modal' => '#modal_search', 'label' => $label, 'accesskey' => 'q']);
    }

    public function addQuickExport(string $url): void
    {
        $this->addAction('download', ['url' => $url, 'class' => 'toolbar-action']);
    }

    public function addCreate(string $url, bool $modal = true): void
    {
        $this->addAction('create', ['url' => $url, 'class' => ($modal ? 'modal-ajax-form' : ''), 'accesskey' => 'a']);
    }

    public function addHelp(string $url): void
    {
        $this->addAction('help', ['url' => $url, 'target' => '_blank', 'accesskey' => 'h']);
    }

    public function addBack(string $url): void
    {
        $this->addAction('back', ['url' => $url, 'translation_domain' => 'actions']);
    }

    public function addDelete(string $url, bool $remoteConfirm = true): void
    {
        if ($remoteConfirm) {
            $this->addAction('trash', ['url' => $url, 'class' => 'modal-ajax-form text-red']);
        } else {
            $this->addAction('trash', ['url' => $url, 'class' => 'confirmation-link text-red', 'attr' => ['data-question' => 'confirm.delete']]);
        }
    }

    public function addColumnToggle(string $modal): void
    {
        $modal = '#' . ltrim($modal, '#');
        $this->addAction('visibility', ['modal' => $modal]);
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
}
