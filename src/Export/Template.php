<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

final class Template implements TemplateInterface
{
    private string $id;
    private string $title;
    /**
     * @var array<int, string>
     */
    private array $columns;
    /**
     * @var array<string, mixed>
     */
    private array $options;
    private ?string $locale;

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param array<int, string> $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
