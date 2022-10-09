<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

interface SamlConfigurationInterface
{
    public function isActivated(): bool;

    public function getTitle(): string;

    public function getProvider(): string;

    public function getAttributeMapping(): array;

    public function getRolesAttribute(): ?string;

    public function getRolesMapping(): array;

    public function isRolesResetOnLogin(): bool;

    public function getConnection(): array;
}
