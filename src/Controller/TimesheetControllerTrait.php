<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Repository\TimesheetRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Helper functions for Timesheet controller
 */
trait TimesheetControllerTrait
{
    /**
     * @var bool
     */
    private $durationOnly = false;

    /**
     * @param bool $durationOnly
     */
    protected function setDurationMode(bool $durationOnly)
    {
        $this->durationOnly = $durationOnly;
    }

    /**
     * @return bool
     */
    protected function isDurationOnlyMode()
    {
        return $this->durationOnly;
    }

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
            if ($editForm->has('duration')) {
                /** @var Timesheet $record */
                $record = $editForm->getData();
                $duration = $editForm->get('duration')->getData();
                $end = null;
                if ($duration > 0) {
                    $end = clone $record->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                }
                $record->setEnd($end);
            }

            // TODO validate that end is not before begin

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute($redirectRoute, ['page' => $request->get('page')]);
        }

        return $this->render($renderTemplate, [
            'entry' => $entry,
            'form' => $editForm->createView(),
        ]);
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
            if ($createForm->has('duration')) {
                $duration = $createForm->get('duration')->getData();
                if ($duration > 0) {
                    /** @var Timesheet $record */
                    $record = $createForm->getData();
                    $end = clone $record->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                    $record->setEnd($end);
                }
            }

            // TODO validate that end is not before begin

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);

            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute($redirectRoute);
        }

        return $this->render($renderTemplate, [
            'entry' => $entry,
            'form' => $createForm->createView(),
        ]);
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

    /**
     * Adds a "successful" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    abstract protected function flashSuccess($translationKey, $parameter = []);

    /**
     * Adds a "error" flash message to the stack.
     *
     * @param $translationKey
     * @param array $parameter
     */
    abstract protected function flashError($translationKey, $parameter = []);

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    abstract protected function getDoctrine(): ManagerRegistry;

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     */
    abstract protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse;

    /**
     * Renders a view.
     */
    abstract protected function render(string $view, array $parameters = [], Response $response = null): Response;

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     * @throws \LogicException If SecurityBundle is not available
     */
    abstract protected function getUser();
}
