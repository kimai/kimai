<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Plugin;

use App\Constants;

class PluginMetadata
{
    private string $package;
    private string $version;
    private int $kimaiVersion;
    private string $homepage;
    private string $description;
    private string $name;

    public static function createFromPath(string $path): self
    {
        if (!is_dir($path) || !is_readable($path)) {
            throw new \Exception(\sprintf('Bundle directory "%s" cannot be accessed.', $path));
        }

        $composer = $path . '/composer.json';

        if (!file_exists($composer) || !is_readable($composer)) {
            throw new \Exception('Bundle does not ship composer.json, which is required since 2.0.');
        }

        /** @var array<mixed>|null $json */
        $json = json_decode(file_get_contents($composer), true);

        if ($json === null) {
            throw new \Exception('Could not parse composer.json, invalid JSON?');
        }

        return self::createFromArray($json);
    }

    /**
     * @param array<mixed> $json
     */
    public static function createFromArray(array $json): self
    {
        if (!\array_key_exists('extra', $json)) {
            throw new \Exception('Bundle "%s" does not define an "extra" node in composer.json, which is required since 2.0.');
        }

        if (!\array_key_exists('kimai', $json['extra'])) {
            throw new \Exception('Bundle does not define the "extra.kimai" node in composer.json, which is required since 2.0.');
        }

        if (!\array_key_exists('require', $json['extra']['kimai'])) {
            throw new \Exception('Bundle does not define the minimum Kimai version in "extra.kimai.required" in composer.json, which is required since 2.0.');
        }

        if (!\array_key_exists('name', $json['extra']['kimai'])) {
            throw new \Exception('Bundle does not define its name in "extra.kimai.name" in composer.json, which is required since 2.0.');
        }

        if (!\is_int($json['extra']['kimai']['require'])) {
            throw new \Exception('Bundle defines an invalid Kimai minimum version in extra.kimai.require. Please provide an integer as in Constants::VERSION_ID.');
        }

        $meta = new self();

        $meta->package = $json['name'] ?? '';
        $meta->description = $json['description'] ?? '';
        $meta->homepage = $json['homepage'] ?? Constants::HOMEPAGE . '/store/';
        $meta->name = $json['extra']['kimai']['name'];
        $meta->kimaiVersion = $json['extra']['kimai']['require'];

        // the version field is required if we use composer to install a plugin via var/packages/
        $meta->version = $json['extra']['kimai']['version'] ?? ($json['version'] ?? 'unknown');

        return $meta;
    }

    private function __construct() {}

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getKimaiVersion(): int
    {
        return $this->kimaiVersion;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
