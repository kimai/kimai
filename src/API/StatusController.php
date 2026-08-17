<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Model\Plugin;
use App\API\Model\Version;
use App\Plugin\PluginManager;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('API')]
#[OA\Tag(name: 'Default')]
final class StatusController extends BaseApiController
{
    /**
     * Testing route for the API
     */
    #[OA\Response(response: 200, description: "A simple route that returns a 'pong', which you can use for testing the API", content: new OA\JsonContent(example: "{'message': 'pong'}"))]
    #[Route(path: '/ping', methods: ['GET'])]
    public function pingAction(ViewHandlerInterface $viewHandler): Response
    {
        $view = new View(['message' => 'pong'], 200);

        return $viewHandler->handle($view);
    }

    /**
     * Fetch Kimai release
     */
    #[OA\Response(response: 200, description: 'Returns version information about the current release', content: new OA\JsonContent(ref: new Model(type: Version::class)))]
    #[Route(path: '/version', methods: ['GET'])]
    public function versionAction(ViewHandlerInterface $viewHandler): Response
    {
        return $viewHandler->handle(new View(new Version(), 200));
    }

    /**
     * Fetch installed Plugins
     */
    #[OA\Response(response: 200, description: 'Returns a list of plugin names and versions', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Plugin::class))))]
    #[Route(path: '/plugins', methods: ['GET'])]
    public function pluginAction(PluginManager $pluginManager, ViewHandlerInterface $viewHandler): Response
    {
        $plugins = [];
        foreach ($pluginManager->getPlugins() as $plugin) {
            $plugins[] = new Plugin($plugin);
        }

        return $viewHandler->handle(new View($plugins, 200));
    }
}
