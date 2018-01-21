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

use App\Entity\Timesheet;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TimesheetRepository;

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
