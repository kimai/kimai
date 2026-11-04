<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet;

final class ColumnDefinition
{
    private $accessor;

    private string $translationDomain = 'messages';
    /**
     * @var string[]
     */
    private array $permissions = [];

    public function __construct(private readonly string $label, private readonly string $type, callable $accessor)
    {
        $this->accessor = $accessor;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccessor(): callable
    {
        return $this->accessor;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function setTranslationDomain(string $translationDomain): void
    {
        $this->translationDomain = $translationDomain;
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * If one of the given "permissions" is granted, the column value will be shown.
     *
     * @param string[] $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }
}
