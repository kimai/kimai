<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/favorite')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class FavoriteController extends AbstractController
{
    #[Route(path: '/timesheet/', name: 'favorites_timesheets', methods: ['GET'])]
    #[IsGranted('start_own_timesheet')]
    public function favoriteAction(): Response
    {
        return $this->render('favorite/index.html.twig');
    }
}
