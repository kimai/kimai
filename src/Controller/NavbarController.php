<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\ActivityRepository;
use App\Timesheet\UserDateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller used to render recent activities and quick-start new recordings in the navigation-bar.
 *
 * @Security("is_granted('ROLE_USER')")
 */
class NavbarController extends AbstractController
{
    /**
     * @param ActivityRepository $repository
     * @param UserDateTimeFactory $dateTimeFactory
     * @return Response
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function recentActivitiesAction(ActivityRepository $repository, UserDateTimeFactory $dateTimeFactory)
    {
        $user = $this->getUser();
        $entries = $repository->getRecentActivities($user, $dateTimeFactory->createDateTime('-1 year'));

        return $this->render(
            'navbar/recent-activities.html.twig',
            ['entries' => $entries]
        );
    }
}
