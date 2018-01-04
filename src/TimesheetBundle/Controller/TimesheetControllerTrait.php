<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Controller;

use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Entity\Timesheet;
use Symfony\Component\HttpFoundation\Request;
use TimesheetBundle\Form\TimesheetToolbarForm;
use TimesheetBundle\Repository\TimesheetRepository;
use TimesheetBundle\Model\Query\Timesheet as TimesheetQuery;

/**
 * Helper functions for Timesheet controller
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
trait TimesheetControllerTrait
{
    /**
     * @return TimesheetRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(Timesheet::class);
    }

    /**
     * @param Request $request
     * @return TimesheetQuery
     */
    protected function getQueryForRequest(Request $request)
    {
        $activity = $request->get('activity');
        $activity = !empty(trim($activity)) ? trim($activity) : null;
        $project = $request->get('project');
        $project = !empty(trim($project)) ? trim($project) : null;
        $customer = $request->get('customer');
        $customer = !empty(trim($customer)) ? trim($customer) : null;
        $pageSize = $request->get('pageSize');

        if ($activity !== null) {
            $repo = $this->getDoctrine()->getRepository(Activity::class);
            $activity = $repo->getById($activity);
            if ($activity !== null) {
                $project = $activity->getProject();
                if ($project !== null) {
                    $customer = $project->getCustomer();
                }
            } else {
                $customer = null;
                $project = null;
            }
        } elseif ($project !== null) {
            $repo = $this->getDoctrine()->getRepository(Project::class);
            $project = $repo->getById($project);
            if ($project !== null) {
                $customer = $project->getCustomer();
            } else {
                $customer = null;
            }
        } else if ($customer !== null) {
            $repo = $this->getDoctrine()->getRepository(Customer::class);
            $customer = $repo->getById($customer);
        }

        $query = new TimesheetQuery();
        $query
            ->setActivity($activity)
            ->setProject($project)
            ->setCustomer($customer)
            ->setPageSize($pageSize);

        return $query ;
    }

    /**
     * @param TimesheetQuery $query
     * @param string $route
     * @return mixed
     */
    protected function getToolbarForm(TimesheetQuery $query, $route = 'timesheet')
    {
        return $this->createForm(
            TimesheetToolbarForm::class,
            $query,
            [
                'action' => $this->generateUrl($route, [
                    'page' => $query->getPage(),
                ]),
                'method' => 'GET',
            ]
        );
    }
}
