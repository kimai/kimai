<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Serializer\RateExclusionStrategy;
use App\Entity\Timesheet;
use App\Timesheet\FavoriteRecordService;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/favorites')]
#[IsGranted('API')]
#[OA\Tag(name: 'Favorites')]
final class FavoritesController extends BaseApiController
{
    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly FavoriteRecordService $favoriteRecordService,
        private readonly AuthorizationCheckerInterface $security
    ) {
    }

    /**
     * Fetch favorite timesheets
     */
    #[IsGranted('start_own_timesheet')]
    #[OA\Response(response: 200, description: 'Returns the favorite timesheets of the current user, which are used as templates to start a new record.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TimesheetCollectionExpanded')))]
    #[Route(path: '/timesheets', name: 'get_favorite_timesheets', methods: ['GET'])]
    public function cgetTimesheets(): Response
    {
        $user = $this->getUser();

        $timesheets = [];
        foreach ($this->favoriteRecordService->favoriteEntries($user, 0) as $favorite) {
            if ($favorite->isFavorite()) {
                $timesheets[] = $favorite->getTimesheet();
            }
        }

        $view = new View($timesheets, Response::HTTP_OK);

        $context = $view->getContext();
        $context->setGroups(array_merge(['Default', 'Collection', 'Timesheet', 'Expanded'], ['Timesheet_Rate']));
        $context->addExclusionStrategy(new RateExclusionStrategy($this->security));

        return $this->viewHandler->handle($view);
    }

    /**
     * Add a timesheet to the favorites
     */
    #[IsGranted('start_own_timesheet')]
    #[IsGranted('is_owner', 'timesheet')]
    #[OA\Post(description: 'Adds the timesheet to the favorites of the current user. Favorites are used as templates to start a new record, only the own records can be favorited.', responses: [new OA\Response(response: 204, description: 'Empty')])]
    #[OA\Parameter(name: 'id', description: 'Timesheet ID to add', in: 'path', required: true)]
    #[Route(path: '/timesheets/{id}', name: 'post_favorite_timesheet', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addTimesheet(Timesheet $timesheet): Response
    {
        $this->favoriteRecordService->addFavorite($timesheet);

        return $this->viewHandler->handle(new View(null, Response::HTTP_NO_CONTENT));
    }

    /**
     * Remove a timesheet from the favorites
     */
    #[IsGranted('start_own_timesheet')]
    #[IsGranted('is_owner', 'timesheet')]
    #[OA\Delete(description: 'Removes the timesheet from the favorites of the current user. Removing a timesheet which is not a favorite is not an error.', responses: [new OA\Response(response: 204, description: 'Empty')])]
    #[OA\Parameter(name: 'id', description: 'Timesheet ID to remove', in: 'path', required: true)]
    #[Route(path: '/timesheets/{id}', name: 'delete_favorite_timesheet', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function removeTimesheet(Timesheet $timesheet): Response
    {
        $this->favoriteRecordService->removeFavorite($timesheet);

        return $this->viewHandler->handle(new View(null, Response::HTTP_NO_CONTENT));
    }
}
