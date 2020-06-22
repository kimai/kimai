<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\TimesheetConfiguration;
use App\Repository\TimesheetRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used for the (initial) page rendering.
 */
class LayoutController extends AbstractController
{
    public function activeEntries(TimesheetRepository $repository, TimesheetConfiguration $configuration): Response
    {
        $user = $this->getUser();
        $activeEntries = $repository->getActiveEntries($user);

        return $this->render(
            'navbar/active-entries.html.twig',
            [
                'entries' => $activeEntries,
                'soft_limit' => $configuration->getActiveEntriesSoftLimit(),
            ]
        );
    }
}
