<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Entity\Configuration;
use App\Form\Model\SystemConfiguration;
use App\Repository\ConfigurationRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ConfigurationService implements ConfigLoaderInterface
{
    /**
     * @var array<string, string|null>
     */
    private static array $cacheAll = [];
    private static bool $initialized = false;

    public function __construct(private ConfigurationRepository $configurationRepository, private CacheInterface $cache)
    {
    }

    /**
     * @return array<string, string|null>
     */
    public function getConfigurations(): array
    {
        if (self::$initialized === true) {
            return self::$cacheAll;
        }

        self::$cacheAll = $this->cache->get('configurations', function (ItemInterface $item) {
            $item->expiresAfter(86400); // one day

            return $this->configurationRepository->getConfigurations();
        });

        self::$initialized = true;

        return self::$cacheAll;
    }

    public function getConfiguration(string $name): ?Configuration
    {
        return $this->configurationRepository->findOneBy(['name' => $name]);
    }

    public function clearCache(): void
    {
        $this->cache->delete('configurations');
        self::$initialized = false;
    }

    public function saveConfiguration(Configuration $configuration): void
    {
        $this->configurationRepository->saveConfiguration($configuration);
        $this->clearCache();
    }

    public function saveSystemConfiguration(SystemConfiguration $model): void
    {
        $this->configurationRepository->saveSystemConfiguration($model);
        $this->clearCache();
    }
}
