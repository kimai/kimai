<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\MetaTableTypeInterface;

final readonly class ExportPreviewColumn
{
    public function __construct(
        private string $repositoryType,
        private string $name,
        private string $label,
        private MetaTableTypeInterface $field,
    )
    {
    }

    public function getRepositoryType(): string
    {
        return $this->repositoryType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getField(): MetaTableTypeInterface
    {
        return $this->field;
    }

    public function getTableKey(): string
    {
        return 'mf_' . bin2hex($this->repositoryType) . '_' . bin2hex($this->name);
    }
}
