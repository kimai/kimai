<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller used to manage activity contents in the public part of the site.
 *
 * @Security("is_granted('ROLE_USER')")
 */
class ActivityController extends AbstractController
{
    /**
     * @return ActivityRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(Activity::class);
    }

    /**
     * The flyout to render recent activities and quick-start new recordings.
     *
     * @return Response
     */
    public function recentActivitiesAction()
    {
        $user = $this->getUser();
        $entries = $this->getRepository()->getRecentActivities($user, new \DateTime('-30 days'));

        return $this->render(
            'navbar/recent-activities.html.twig',
            ['entries' => $entries]
        );
    }
}
