<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\ActivityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller used to manage navigation-bar contents.
 *
 * @Security("is_granted('ROLE_USER')")
 */
class NavbarController extends AbstractController
{
    /**
     * @var ActivityRepository
     */
    private $repository;

    /**
     * @param ActivityRepository $repository
     */
    public function __construct(ActivityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return ActivityRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * The flyout to render recent activities and quick-start new recordings.
     *
     * @return Response
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function recentActivitiesAction()
    {
        $user = $this->getUser();
        $entries = $this->getRepository()->getRecentActivities($user, new \DateTime('-1 year'));

        return $this->render(
            'navbar/recent-activities.html.twig',
            ['entries' => $entries]
        );
    }
}
