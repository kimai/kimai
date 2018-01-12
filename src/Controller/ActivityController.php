<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Activity;
use App\Repository\ActivityRepository;

/**
 * Controller used to manage activity contents in the public part of the site.
 *
 * @Route("/activity")
 * @Security("has_role('ROLE_USER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityController extends Controller
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recentActivitiesAction()
    {
        $user = $this->getUser();
        // TODO make days configurable
        $activeEntries = $this->getRepository()->getRecentActivities($user, new \DateTime('-30 days'));

        return $this->render(
            'navbar/recent-activities.html.twig',
            ['activities' => $activeEntries]
        );
    }
}
