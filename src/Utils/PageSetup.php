<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class PageSetup
{
    private ?string $help = null;
    private ?string $actionName = null;
    private string $actionView = 'index';
    private string $translationDomain = 'messages';
    private array $actionPayload = [];
    private ?DataTable $dataTable = null;

    public function __construct(private string $title)
    {
    }

    public function hasDataTable(): bool
    {
        return $this->dataTable !== null;
    }

    public function hasSearchForm(): bool
    {
        return $this->dataTable !== null && $this->dataTable->getSearchForm() !== null;
    }

    public function getDataTable(): ?DataTable
    {
        return $this->dataTable;
    }

    public function setDataTable(?DataTable $dataTable): void
    {
        $this->dataTable = $dataTable;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): void
    {
        $this->help = $help;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getActionName(): ?string
    {
        return $this->actionName;
    }

    public function setActionName(?string $actionName): void
    {
        $this->actionName = $actionName;
    }

    public function getActionView(): string
    {
        return $this->actionView;
    }

    public function setActionView(string $actionView): void
    {
        $this->actionView = $actionView;
    }

    public function getActionPayload(): array
    {
        return $this->actionPayload;
    }

    public function setActionPayload(array $actionPayload): void
    {
        $this->actionPayload = $actionPayload;
    }

    public function isTableAction(): bool
    {
        return \in_array($this->actionView, ['detail', 'custom', 'table']);
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function setTranslationDomain(string $translationDomain): void
    {
        $this->translationDomain = $translationDomain;
    }
}
