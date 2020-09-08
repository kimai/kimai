<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Calendar\Google;
use App\Calendar\Source;
use App\Configuration\CalendarConfiguration;
use App\Timesheet\TrackingModeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to display calendars.
 *
 * @Route(path="/calendar")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class CalendarController extends AbstractController
{
    /**
     * @Route(path="/", name="calendar", methods={"GET"})
     */
    public function userCalendar(CalendarConfiguration $configuration, TrackingModeService $service)
    {
        $mode = $service->getActiveMode();

        return $this->render('calendar/user.html.twig', [
            'config' => $configuration,
            'google' => $this->getGoogleSources($configuration),
            'now' => $this->getDateTimeFactory()->createDateTime(),
            'is_punch_mode' => !$mode->canEditDuration() && !$mode->canEditBegin() && !$mode->canEditEnd()
        ]);
    }

    /**
     * @return Google
     */
    protected function getGoogleSources(CalendarConfiguration $configuration)
    {
        $apiKey = $configuration->getGoogleApiKey() ?? null;
        $sources = [];

        foreach ($configuration->getGoogleSources() as $name => $config) {
            $source = new Source();
            $source
                ->setColor($config['color'])
                ->setUri($config['id'])
                ->setId($name)
            ;

            $sources[] = $source;
        }

        return new Google($apiKey, $sources);
    }
}
