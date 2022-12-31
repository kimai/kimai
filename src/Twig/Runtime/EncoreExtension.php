<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class EncoreExtension implements RuntimeExtensionInterface, ServiceSubscriberInterface
{
    private string $publicDir;

    public function __construct(private ContainerInterface $container, string $projectDirectory)
    {
        $this->publicDir = $projectDirectory . '/public';
    }

    public static function getSubscribedServices(): array
    {
        return [
            EntrypointLookupInterface::class,
        ];
    }

    public function getEncoreEntryCssSource(string $packageName): string
    {
        $lookup = $this->container->get(EntrypointLookupInterface::class);
        $files = $lookup->getCssFiles($packageName);

        $source = '';

        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir . '/' . $file);
        }

        $lookup->reset();

        return $source;
    }
}
