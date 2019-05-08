<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Timesheet\UserDateTimeFactory;
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
     * @var UserDateTimeFactory
     */
    protected $dateTime;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    /**
     * @param UserDateTimeFactory $dateTime
     * @param TimesheetConfiguration $configuration
     */
    public function __construct(UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
    }

    /**
     * @return int
     */
    protected function getHardLimit()
    {
        return $this->configuration->getActiveEntriesHardLimit();
    }

    /**
     * @return int
     */
    protected function getSoftLimit()
    {
        return $this->configuration->getActiveEntriesSoftLimit();
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
     * @param Request $request
     * @param string $redirectRoute
     * @param string $renderTemplate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function edit(Timesheet $entry, Request $request, $redirectRoute, $renderTemplate)
    {
        $editForm = $this->getEditForm($entry, $request->get('page'), $request->get('origin', 'timesheet'));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute($redirectRoute, ['page' => $request->get('page', 1)]);
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
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
        $entry->setBegin($this->dateTime->createDateTime());

        $start = $request->get('begin');
        if ($start !== null) {
            $start = $this->dateTime->createDateTimeFromFormat('Y-m-d', $start);
            if ($start !== false) {
                $start->setTime(10, 0, 0); // TODO make me configurable
                $entry->setBegin($start);
            }
        }

        $end = $request->get('end');
        if ($end !== null) {
            $end = $this->dateTime->createDateTimeFromFormat('Y-m-d', $end);
            if ($end !== false) {
                $end->setTime(18, 0, 0); // TODO make me configurable
                $entry->setEnd($end);
            }
        }

        $from = $request->get('from');
        if ($from !== null) {
            $from = $this->dateTime->createDateTime($from);
            if ($from !== false) {
                $entry->setBegin($from);
            }
        }

        $to = $request->get('to');
        if ($to !== null) {
            $to = $this->dateTime->createDateTime($to);
            if ($to !== false) {
                $entry->setEnd($to);
            }
        }

        $createForm = $this->getCreateForm($entry, $redirectRoute);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            try {
                if (null === $entry->getEnd()) {
                    $this->stopActiveEntries($entry->getUser());
                }
                $entityManager->persist($entry);
                $entityManager->flush();

                $this->flashSuccess('action.update.success');
            } catch (\Exception $ex) {
                $this->flashError('timesheet.start.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute($redirectRoute);
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $createForm->createView(),
        ]);
    }

    /**
     * @param User $user
     * @throws \App\Repository\RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function stopActiveEntries(User $user)
    {
        $this->getRepository()->stopActiveEntries($user, $this->getHardLimit());
    }

    /**
     * @param Timesheet $entry
     * @param string $redirectRoute
     * @return \Symfony\Component\Form\FormInterface
     */
    abstract protected function getCreateForm(Timesheet $entry, string $redirectRoute);

    /**
     * @param Timesheet $entry
     * @param int $page
     * @param string $redirectRoute
     * @return \Symfony\Component\Form\FormInterface
     */
    abstract protected function getEditForm(Timesheet $entry, $page, string $redirectRoute);

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
     * @return User
     * @throws \LogicException If SecurityBundle is not available
     */
    abstract protected function getUser();
}
