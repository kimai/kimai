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
use TimesheetBundle\Form\Toolbar\TimesheetToolbarForm;
use TimesheetBundle\Repository\Query\TimesheetQuery;
use TimesheetBundle\Repository\TimesheetRepository;

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
        $state = $request->get('state');
        $state = !empty(trim($state)) ? trim($state) : null;
        $pageSize = (int) $request->get('pageSize');

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
        } elseif ($customer !== null) {
            $repo = $this->getDoctrine()->getRepository(Customer::class);
            $customer = $repo->getById($customer);
        }

        $query = new TimesheetQuery();
        $query
            ->setActivity($activity)
            ->setProject($project)
            ->setCustomer($customer)
            ->setPageSize($pageSize)
            ->setState($state);

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

    /**
     * @param Timesheet $entry
     * @param string $route
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function stop(Timesheet $entry, $route)
    {
        try {
            $this->getRepository()->stopRecording($entry);
            $this->flashSuccess('timesheet.stop.success');
        } catch (\Exception $ex) {
            $this->flashError('timesheet.stop.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute($route);
    }

    /**
     * @param Timesheet $entry
     * @param Request $request
     * @param string $redirectRoute
     * @param string $renderTemplate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function edit(Timesheet $entry, Request $request, $redirectRoute, $renderTemplate)
    {
        $editForm = $this->getEditForm($entry, $request->get('page'));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute($redirectRoute, ['page' => $request->get('page')]);
        }

        return $this->render(
            $renderTemplate,
            [
                'entry' => $entry,
                'form' => $editForm->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param string $redirectRoute
     * @param string $renderTemplate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function create(Request $request, $redirectRoute, $renderTemplate)
    {
        $entry = new Timesheet();
        $entry->setUser($this->getUser());
        $entry->setBegin(new \DateTime());

        $createForm = $this->getCreateForm($entry);

        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);

            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute($redirectRoute);
        }

        return $this->render(
            $renderTemplate,
            [
                'entry' => $entry,
                'form' => $createForm->createView(),
            ]
        );
    }

    /**
     * @param Timesheet $entry
     * @return \Symfony\Component\Form\FormInterface
     */
    abstract protected function getCreateForm(Timesheet $entry);

    /**
     * @param Timesheet $entry
     * @param int $page
     * @return \Symfony\Component\Form\FormInterface
     */
    abstract protected function getEditForm(Timesheet $entry, $page);

}
