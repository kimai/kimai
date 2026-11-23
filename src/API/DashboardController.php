<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Widget\DashboardService;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/dashboard')]
#[IsGranted('API')]
#[OA\Tag(name: 'Dashboard')]
final class DashboardController extends BaseApiController
{
    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly DashboardService $dashboardService
    ) {
    }

    /**
     * Add a widget to the users dashboard
     */
    #[OA\Post(description: 'Adds the widget to the dashboard of the current user. Adding a widget twice is not an error, the dashboard stays unchanged.', responses: [new OA\Response(response: 204, description: 'Empty')], x: ['internal' => true])]
    #[OA\Parameter(name: 'widget', description: 'Widget ID to add', in: 'path', required: true)]
    #[Route(path: '/widgets/{widget}', name: 'post_dashboard_widget', methods: ['POST'])]
    public function addWidget(string $widget): Response
    {
        $user = $this->getUser();

        // the widget has to exist and the user must be allowed to see it, otherwise arbitrary
        // strings would end up in the stored dashboard configuration
        $selectable = $this->dashboardService->findSelectableWidget($user, $widget);

        if ($selectable === null) {
            throw $this->createNotFoundException('Unknown widget: ' . $widget);
        }

        $this->dashboardService->addUserWidget($user, $selectable);

        return $this->viewHandler->handle(new View(null, Response::HTTP_NO_CONTENT));
    }

    /**
     * Reset the users dashboard
     */
    #[OA\Delete(description: 'Removes the dashboard configuration of the current user, the default dashboard is shown afterwards.', responses: [new OA\Response(response: 204, description: 'Empty')], x: ['internal' => true])]
    #[Route(path: '/widgets', name: 'delete_dashboard_widgets', methods: ['DELETE'])]
    public function resetWidgets(): Response
    {
        $this->dashboardService->resetUserWidgets($this->getUser());

        return $this->viewHandler->handle(new View(null, Response::HTTP_NO_CONTENT));
    }
}
