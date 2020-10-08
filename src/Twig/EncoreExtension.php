<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EncoreExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /**
     * @var EntrypointLookupInterface
     */
    private $encoreService;
    /**
     * @var string
     */
    private $publicDir;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, string $projectDirectory)
    {
        $this->container = $container;
        $this->publicDir = $projectDirectory . '/public';
    }

    public static function getSubscribedServices()
    {
        return [
            EntrypointLookupInterface::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource']),
        ];
    }

    public function getEncoreEntryCssSource(string $packageName): string
    {
        $files = $this->container
            ->get(EntrypointLookupInterface::class)->getCssFiles($packageName);

        $source = '';

        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir . '/' . $file);
        }

        return $source;
    }
}
