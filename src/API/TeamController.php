<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Form\API\TeamApiEditForm;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TeamQuery;
use App\Repository\TeamRepository;
use App\User\TeamService;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/teams')]
#[IsGranted('API')]
#[OA\Tag(name: 'Team')]
final class TeamController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Team', 'Team_Entity', 'Not_Expanded'];
    public const GROUPS_FORM = ['Default', 'Entity', 'Team', 'Team_Entity', 'Not_Expanded'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Team'];

    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly TeamRepository $repository,
        private readonly TeamService $teamService
    )
    {
    }

    /**
     * Fetch teams
     */
    #[IsGranted('view_team')]
    #[OA\Response(response: 200, description: 'Returns a collection of teams', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TeamCollection')))]
    #[Route(methods: ['GET'], path: '', name: 'get_teams')]
    public function cgetAction(): Response
    {
        $query = new TeamQuery();
        $query->setCurrentUser($this->getUser());

        $data = $this->repository->getTeamsForQuery($query);

        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Fetch team
     */
    #[IsGranted('view_team')]
    #[OA\Response(response: 200, description: 'Returns one team entity', content: new OA\JsonContent(ref: '#/components/schemas/Team'))]
    #[Route(methods: ['GET'], path: '/{id}', name: 'get_team', requirements: ['id' => '\d+'])]
    public function getAction(Team $team): Response
    {
        $view = new View($team, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete team
     */
    #[IsGranted('delete_team')]
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Delete one team')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Team ID to delete', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}', name: 'delete_team', requirements: ['id' => '\d+'])]
    public function deleteAction(Team $team): Response
    {
        $this->teamService->deleteTeam($team);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Create team
     */
    #[IsGranted('create_team')]
    #[OA\Post(description: 'Creates a new team and returns it afterwards', responses: [new OA\Response(response: 200, description: 'Returns the new created team', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TeamEditForm'))]
    #[Route(methods: ['POST'], path: '', name: 'post_team')]
    public function postAction(Request $request): Response
    {
        $team = $this->teamService->createNewTeam('');

        $form = $this->createForm(TeamApiEditForm::class, $team);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->teamService->saveTeam($team);

            $view = new View($team, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update team
     */
    #[IsGranted('edit_team')]
    #[OA\Patch(description: 'Update an existing team, you can pass all or just a subset of all attributes (passing members will replace all existing ones)', responses: [new OA\Response(response: 200, description: 'Returns the updated team', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TeamEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Team ID to update', required: true)]
    #[Route(methods: ['PATCH'], path: '/{id}', name: 'patch_team', requirements: ['id' => '\d+'])]
    public function patchAction(Request $request, Team $team): Response
    {
        if ($request->request->has('members')) {
            foreach ($team->getMembers() as $member) {
                $team->removeMember($member);
                $this->repository->removeTeamMember($member);
            }
            // this fails, if we use the teamservice, because the validator
            // complains about teams without members or teamleads
            $this->repository->saveTeam($team);
        }

        $form = $this->createForm(TeamApiEditForm::class, $team);

        $form->setData($team);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_FORM);

            return $this->viewHandler->handle($view);
        }

        $this->teamService->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Add team member
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new user to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team which will receive the new member', required: true)]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'The team member to add (User ID)', required: true)]
    #[Route(methods: ['POST'], path: '/{id}/members/{userId}', name: 'post_team_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    public function postMemberAction(Team $team, #[MapEntity(mapping: ['userId' => 'id'])] User $member): Response
    {
        if ($member->isInTeam($team)) {
            throw new BadRequestHttpException('User is already member of the team');
        }

        $team->addUser($member);

        $this->teamService->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Remove team member
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a user from the team. The teamlead cannot be removed.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team from which the member will be removed', required: true)]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'The team member to remove (User ID)', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}/members/{userId}', name: 'delete_team_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    public function deleteMemberAction(Team $team, #[MapEntity(mapping: ['userId' => 'id'])] User $member): Response
    {
        if (!$member->isInTeam($team)) {
            throw new BadRequestHttpException('User is not a member of the team');
        }

        if ($team->isTeamlead($member)) {
            throw new BadRequestHttpException('Cannot remove teamlead');
        }

        $team->removeUser($member);

        $this->teamService->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant team access to customer
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new customer to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team that is granted access', required: true)]
    #[OA\Parameter(name: 'customerId', in: 'path', description: 'The customer to grant acecess to (Customer ID)', required: true)]
    #[Route(methods: ['POST'], path: '/{id}/customers/{customerId}', name: 'post_team_customer', requirements: ['id' => '\d+', 'customerId' => '\d+'])]
    public function postCustomerAction(Team $team, #[MapEntity(mapping: ['customerId' => 'id'])] Customer $customer, CustomerRepository $customerRepository): Response
    {
        if ($team->hasCustomer($customer)) {
            throw new BadRequestHttpException('Team has already access to customer');
        }

        $team->addCustomer($customer);
        $customerRepository->saveCustomer($customer);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revoke customer access from team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a customer from the team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team whose permission will be revoked', required: true)]
    #[OA\Parameter(name: 'customerId', in: 'path', description: 'The customer to remove (Customer ID)', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}/customers/{customerId}', name: 'delete_team_customer', requirements: ['id' => '\d+', 'customerId' => '\d+'])]
    public function deleteCustomerAction(Team $team, #[MapEntity(mapping: ['customerId' => 'id'])] Customer $customer, CustomerRepository $customerRepository): Response
    {
        if (!$team->hasCustomer($customer)) {
            throw new BadRequestHttpException('Customer is not assigned to the team');
        }

        $team->removeCustomer($customer);
        $customerRepository->saveCustomer($customer);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant team access to project
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new project to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team that is granted access', required: true)]
    #[OA\Parameter(name: 'projectId', in: 'path', description: 'The project to grant acecess to (Project ID)', required: true)]
    #[Route(methods: ['POST'], path: '/{id}/projects/{projectId}', name: 'post_team_project', requirements: ['id' => '\d+', 'projectId' => '\d+'])]
    public function postProjectAction(Team $team, #[MapEntity(mapping: ['projectId' => 'id'])] Project $project, ProjectRepository $projectRepository): Response
    {
        if ($team->hasProject($project)) {
            throw new BadRequestHttpException('Team has already access to project');
        }

        $team->addProject($project);
        $projectRepository->saveProject($project);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revoke project access from team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a project from the team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team whose permission will be revoked', required: true)]
    #[OA\Parameter(name: 'projectId', in: 'path', description: 'The project to remove (Project ID)', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}/projects/{projectId}', name: 'delete_team_project', requirements: ['id' => '\d+', 'projectId' => '\d+'])]
    public function deleteProjectAction(Team $team, #[MapEntity(mapping: ['projectId' => 'id'])] Project $project, ProjectRepository $projectRepository): Response
    {
        if (!$team->hasProject($project)) {
            throw new BadRequestHttpException('Project is not assigned to the team');
        }

        $team->removeProject($project);
        $projectRepository->saveProject($project);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant team access to activity
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new activity to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team that is granted access', required: true)]
    #[OA\Parameter(name: 'activityId', in: 'path', description: 'The activity to grant acecess to (Activity ID)', required: true)]
    #[Route(methods: ['POST'], path: '/{id}/activities/{activityId}', name: 'post_team_activity', requirements: ['id' => '\d+', 'activityId' => '\d+'])]
    public function postActivityAction(Team $team, #[MapEntity(mapping: ['activityId' => 'id'])] Activity $activity, ActivityRepository $activityRepository): Response
    {
        if ($team->hasActivity($activity)) {
            throw new BadRequestHttpException('Team has already access to activity');
        }

        $team->addActivity($activity);
        $activityRepository->saveActivity($activity);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revoke activity access from team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a activity from the team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team whose permission will be revoked', required: true)]
    #[OA\Parameter(name: 'activityId', in: 'path', description: 'The activity to remove (Activity ID)', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}/activities/{activityId}', name: 'delete_team_activity', requirements: ['id' => '\d+', 'activityId' => '\d+'])]
    public function deleteActivityAction(Team $team, #[MapEntity(mapping: ['activityId' => 'id'])] Activity $activity, ActivityRepository $activityRepository): Response
    {
        if (!$team->hasActivity($activity)) {
            throw new BadRequestHttpException('Activity is not assigned to the team');
        }

        $team->removeActivity($activity);
        $activityRepository->saveActivity($activity);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }
}
