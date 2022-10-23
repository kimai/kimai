<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Timesheet\FavoriteRecordService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/favorite")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
final class FavoriteController extends AbstractController
{
    /**
     * @Route(path="/timesheet/", name="favorites_timesheets", methods={"GET"})
     * @Security("is_granted('view_own_timesheet')")
     */
    public function favoriteAction(): Response
    {
        return $this->render('partials/recent-activities.html.twig');
    }

    /**
     * @Route(path="/timesheet/add/{id}", name="favorites_timesheets_add", methods={"GET"})
     * @Security("is_granted('view_own_timesheet')")
     */
    public function add(Timesheet $timesheet, FavoriteRecordService $favoriteRecordService): Response
    {
        $favoriteRecordService->addFavorite($timesheet);

        return $this->render('partials/recent-activities.html.twig');
    }

    /**
     * @Route(path="/timesheet/remove/{id}", name="favorites_timesheets_remove", methods={"GET"})
     * @Security("is_granted('view_own_timesheet')")
     */
    public function remove(Timesheet $timesheet, FavoriteRecordService $favoriteRecordService): Response
    {
        $favoriteRecordService->removeFavorite($timesheet);

        return $this->render('partials/recent-activities.html.twig');
    }
}