<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Plugin\PluginManager;
use App\Utils\PageSetup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route(path: '/admin/plugins')]
#[IsGranted('plugins')]
final class PluginController extends AbstractController
{
    #[Route(path: '/', name: 'plugins', methods: ['GET'])]
    public function indexAction(PluginManager $manager, HttpClientInterface $client, CacheInterface $cache): Response
    {
        $installed = [];
        $plugins = $manager->getPlugins();
        foreach ($plugins as $plugin) {
            $installed[$plugin->getId()] = $plugin;
        }

        $page = new PageSetup('menu.plugin');
        $page->setHelp('plugins.html');

        $all = $this->getPluginInformation($client, $cache);
        $bundles = [];
        $updates = [];
        foreach ($all as $item) {
            if ($item['bundle'] !== null) {
                $bundles[$item['bundle']] = $item;
                if (\array_key_exists($item['bundle'], $installed)) {
                    $updates[$item['bundle']] = version_compare($installed[$item['bundle']]->getMetadata()->getVersion(), $item['latest_release']) === -1;
                }
            }
        }

        return $this->render('plugin/index.html.twig', [
            'page_setup' => $page,
            'plugins' => $plugins,
            'installed' => array_keys($installed),
            'extensions' => $all,
            'bundles' => $bundles,
            'updates' => $updates,
        ]);
    }

    private function getPluginInformation(HttpClientInterface $client, CacheInterface $cache): array
    {
        return $cache->get('kimai.marketplace_extensions', function (ItemInterface $item) use ($client) {
            try {
                $response = $client->request('GET', 'https://www.kimai.org/plugins.json');

                if ($response->getStatusCode() !== 200) {
                    return [];
                }

                $json = json_decode($response->getContent(), true);

                if ($json === null) {
                    return [];
                }

                $item->expiresAfter(86400); // one day

                return $response->toArray();
            } catch (\Throwable $exception) {
                $this->logException($exception);
                $this->flashError('Could not download plugin information');
            }

            return [];
        });
    }
}
