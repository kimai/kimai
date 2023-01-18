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
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[OA\Tag(name: 'Default')]
final class StatusController extends BaseApiController
{
    public function __construct(private ViewHandlerInterface $viewHandler)
    {
    }

    /**
     * A testing route for the API
     */
    #[OA\Response(response: 200, description: "A simple route that returns a 'pong', which you can use for testing the API", content: new OA\JsonContent(example: "{'message': 'pong'}"))]
    #[Rest\Get(path: '/ping')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function pingAction(): Response
    {
        $view = new View(['message' => 'pong'], 200);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns information about the Kimai release
     */
    #[OA\Response(response: 200, description: 'Returns version information about the current release', content: new OA\JsonContent(ref: new Model(type: Version::class)))]
    #[Rest\Get(path: '/version')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function versionAction(): Response
    {
        return $this->viewHandler->handle(new View(new Version(), 200));
    }

    /**
     * Returns information about installed Plugins
     */
    #[OA\Response(response: 200, description: 'Returns a list of plugin names and versions', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Plugin::class))))]
    #[Rest\Get(path: '/plugins')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function pluginAction(PluginManager $pluginManager): Response
    {
        $plugins = [];
        foreach ($pluginManager->getPlugins() as $plugin) {
            $pluginManager->loadMetadata($plugin);
            $plugins[] = new Plugin($plugin);
        }

        return $this->viewHandler->handle(new View($plugins, 200));
    }
}
