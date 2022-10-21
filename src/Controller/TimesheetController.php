<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Event\PageActionsEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\ServiceExport;
use App\Form\TimesheetEditForm;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/timesheet")
 * @Security("is_granted('view_own_timesheet')")
 */
class TimesheetController extends TimesheetAbstractController
{
    /**
     * @Route(path="/", defaults={"page": 1}, name="timesheet", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="timesheet_paginated", methods={"GET"})
     * @Security("is_granted('view_own_timesheet')")
     */
    public function indexAction(int $page, Request $request): Response
    {
        $query = $this->createDefaultQuery();
        $query->setPage($page);

        return $this->index($query, $request, 'timesheet', 'timesheet_paginated', TimesheetMetaDisplayEvent::TIMESHEET);
    }

    /**
     * @Route(path="/{id}/actions/{view}", name="get_timesheet_actions", requirements={"id": "\d+"}, defaults={"view": "custom"}, methods={"GET"})
     * @Security("is_granted('view', timesheet)")
     */
    public function getActions(Timesheet $timesheet, string $view): Response
    {
        $themeEvent = new PageActionsEvent($this->getUser(), ['timesheet' => $timesheet], 'timesheet', $view);
        $this->dispatcher->dispatch($themeEvent, $themeEvent->getEventName());

        $translator = $this->getTranslator();

        $all = [];
        foreach ($themeEvent->getActions() as $timesheet => $action) {
            if ($action !== null) {
                $domain = \array_key_exists('translation_domain', $action) ? $action['translation_domain'] : 'messages';
                if (!\array_key_exists('title', $action)) {
                    $action['title'] = $translator->trans($timesheet, [], $domain);
                } else {
                    $action['title'] = $translator->trans($action['title'], [], $domain);
                }
            }
            $all[$timesheet] = $action;
        }

        return $this->json($all);
    }

    /**
     * @Route(path="/export/", name="timesheet_export", methods={"GET", "POST"})
     * @Security("is_granted('export_own_timesheet')")
     */
    public function exportAction(Request $request, ServiceExport $serviceExport): Response
    {
        return $this->export($request, $serviceExport);
    }

    /**
     * @Route(path="/{id}/edit", name="timesheet_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     */
    public function editAction(Timesheet $entry, Request $request): Response
    {
        return $this->edit($entry, $request);
    }

    /**
     * @Route(path="/{id}/duplicate", name="timesheet_duplicate", methods={"GET", "POST"})
     * @Security("is_granted('duplicate', entry)")
     */
    public function duplicateAction(Timesheet $entry, Request $request): Response
    {
        return $this->duplicate($entry, $request);
    }

    /**
     * @Route(path="/multi-update", name="timesheet_multi_update", methods={"POST"})
     * @Security("is_granted('edit_own_timesheet')")
     */
    public function multiUpdateAction(Request $request): Response
    {
        return $this->multiUpdate($request);
    }

    /**
     * @Route(path="/multi-delete", name="timesheet_multi_delete", methods={"POST"})
     * @Security("is_granted('delete_own_timesheet')")
     */
    public function multiDeleteAction(Request $request): Response
    {
        return $this->multiDelete($request);
    }

    /**
     * @Route(path="/create", name="timesheet_create", methods={"GET", "POST"})
     * @Security("is_granted('create_own_timesheet')")
     */
    public function createAction(Request $request): Response
    {
        return $this->create($request);
    }

    protected function getCreateForm(Timesheet $entry): FormInterface
    {
        return $this->generateCreateForm($entry, TimesheetEditForm::class, $this->generateUrl('timesheet_create'));
    }

    protected function getDuplicateForm(Timesheet $entry, Timesheet $original): FormInterface
    {
        return $this->generateCreateForm($entry, TimesheetEditForm::class, $this->generateUrl('timesheet_duplicate', ['id' => $original->getId()]));
    }
}
