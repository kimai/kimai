<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamEditForm;
use App\Form\Toolbar\TeamToolbarForm;
use App\Repository\Query\TeamQuery;
use App\Repository\TeamRepository;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/teams")
 * @Security("is_granted('view_team')")
 */
class TeamController extends AbstractController
{
    /**
     * @var TeamRepository
     */
    private $repository;

    public function __construct(TeamRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_team", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_team_paginated", methods={"GET"})
     *
     * @param TeamRepository $repository
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function listTeams(TeamRepository $repository, Request $request, $page)
    {
        $query = new TeamQuery();
        $query->setPage($page);
        $query->setOrderBy('name');

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        $teams = $repository->getPagerfantaForQuery($query);

        return $this->render('team/index.html.twig', [
            'teams' => $teams,
            'query' => $query,
            'showFilter' => $query->isDirty(),
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/create", name="admin_team_create", methods={"GET", "POST"})
     * @Security("is_granted('create_team')")
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createTeam(Request $request)
    {
        $team = new Team();

        return $this->renderTeamForm($team, $request);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_team_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', team)")
     *
     * @param Team $team
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editAction(Team $team, Request $request)
    {
        return $this->renderTeamForm($team, $request);
    }

    protected function getToolbarForm(TeamQuery $query): FormInterface
    {
        return $this->createForm(TeamToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_team', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    /**
     * @param Team $team
     * @param Request $request
     * @return RedirectResponse|Response
     */
    protected function renderTeamForm(Team $team, Request $request)
    {
        if ($team->getId() === null) {
            $url = $this->generateUrl('admin_team_create');
        } else {
            $url = $this->generateUrl('admin_team_edit', ['id' => $team->getId()]);
        }

        $editForm = $this->createForm(TeamEditForm::class, $team, [
            'action' => $url,
            'method' => 'POST',
        ]);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->repository->saveTeam($team);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_team');
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $editForm->createView()
        ]);
    }
}
