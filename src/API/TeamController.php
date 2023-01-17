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
use App\Repository\TeamRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/teams')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[OA\Tag(name: 'Team')]
final class TeamController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Team', 'Team_Entity', 'Not_Expanded'];
    public const GROUPS_FORM = ['Default', 'Entity', 'Team', 'Team_Entity', 'Not_Expanded'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Team'];

    public function __construct(private ViewHandlerInterface $viewHandler, private TeamRepository $repository)
    {
    }

    /**
     * Fetch all existing teams (which are visible to the user)
     */
    #[IsGranted('view_team')]
    #[OA\Response(response: 200, description: 'Returns the collection of teams', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TeamCollection')))]
    #[Rest\Get(path: '', name: 'get_teams')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function cgetAction(): Response
    {
        $data = $this->repository->findAll();

        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one team
     */
    #[IsGranted('view_team')]
    #[OA\Response(response: 200, description: 'Returns one team entity', content: new OA\JsonContent(ref: '#/components/schemas/Team'))]
    #[Rest\Get(path: '/{id}', name: 'get_team', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getAction(Team $team): Response
    {
        $view = new View($team, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete a team
     */
    #[IsGranted('delete_team')]
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Delete one team')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Team ID to delete', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}', name: 'delete_team', requirements: ['id' => '\d+'])]
    public function deleteAction(Team $team): Response
    {
        $this->repository->deleteTeam($team);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new team
     */
    #[IsGranted('create_team')]
    #[OA\Post(description: 'Creates a new team and returns it afterwards', responses: [new OA\Response(response: 200, description: 'Returns the new created team', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TeamEditForm'))]
    #[Rest\Post(path: '', name: 'post_team')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postAction(Request $request): Response
    {
        $team = new Team('');

        $form = $this->createForm(TeamApiEditForm::class, $team);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveTeam($team);

            $view = new View($team, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing team
     */
    #[IsGranted('edit_team')]
    #[OA\Patch(description: 'Update an existing team, you can pass all or just a subset of all attributes (passing members will replace all existing ones)', responses: [new OA\Response(response: 200, description: 'Returns the updated team', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TeamEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Team ID to update', required: true)]
    #[Rest\Patch(path: '/{id}', name: 'patch_team', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function patchAction(Request $request, Team $team): Response
    {
        if ($request->request->has('members')) {
            foreach ($team->getMembers() as $member) {
                $team->removeMember($member);
                $this->repository->removeTeamMember($member);
            }
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

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Add a new member to a team
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new user to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team which will receive the new member', required: true)]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'The team member to add (User ID)', required: true)]
    #[Rest\Post(path: '/{id}/members/{userId}', name: 'post_team_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postMemberAction(Team $team, #[MapEntity(mapping: ['userId' => 'id'])] User $member): Response
    {
        if ($member->isInTeam($team)) {
            throw new BadRequestHttpException('User is already member of the team');
        }

        $team->addUser($member);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Removes a member from the team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a user from the team. The teamlead cannot be removed.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team from which the member will be removed', required: true)]
    #[OA\Parameter(name: 'userId', in: 'path', description: 'The team member to remove (User ID)', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}/members/{userId}', name: 'delete_team_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    public function deleteMemberAction(Team $team, #[MapEntity(mapping: ['userId' => 'id'])] User $member): Response
    {
        if (!$member->isInTeam($team)) {
            throw new BadRequestHttpException('User is not a member of the team');
        }

        if ($team->isTeamlead($member)) {
            throw new BadRequestHttpException('Cannot remove teamlead');
        }

        $team->removeUser($member);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant the team access to a customer
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new customer to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team that is granted access', required: true)]
    #[OA\Parameter(name: 'customerId', in: 'path', description: 'The customer to grant acecess to (Customer ID)', required: true)]
    #[Rest\Post(path: '/{id}/customers/{customerId}', name: 'post_team_customer', requirements: ['id' => '\d+', 'customerId' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postCustomerAction(Team $team, #[MapEntity(mapping: ['customerId' => 'id'])] Customer $customer): Response
    {
        if ($team->hasCustomer($customer)) {
            throw new BadRequestHttpException('Team has already access to customer');
        }

        $team->addCustomer($customer);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revokes access for a customer from a team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a customer from the team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team whose permission will be revoked', required: true)]
    #[OA\Parameter(name: 'customerId', in: 'path', description: 'The customer to remove (Customer ID)', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}/customers/{customerId}', name: 'delete_team_customer', requirements: ['id' => '\d+', 'customerId' => '\d+'])]
    public function deleteCustomerAction(Team $team, #[MapEntity(mapping: ['customerId' => 'id'])] Customer $customer): Response
    {
        if (!$team->hasCustomer($customer)) {
            throw new BadRequestHttpException('Customer is not assigned to the team');
        }

        $team->removeCustomer($customer);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant the team access to a project
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new project to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team that is granted access', required: true)]
    #[OA\Parameter(name: 'projectId', in: 'path', description: 'The project to grant acecess to (Project ID)', required: true)]
    #[Rest\Post(path: '/{id}/projects/{projectId}', name: 'post_team_project', requirements: ['id' => '\d+', 'projectId' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postProjectAction(Team $team, #[MapEntity(mapping: ['projectId' => 'id'])] Project $project): Response
    {
        if ($team->hasProject($project)) {
            throw new BadRequestHttpException('Team has already access to project');
        }

        $team->addProject($project);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revokes access for a project from a team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a project from the team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team whose permission will be revoked', required: true)]
    #[OA\Parameter(name: 'projectId', in: 'path', description: 'The project to remove (Project ID)', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}/projects/{projectId}', name: 'delete_team_project', requirements: ['id' => '\d+', 'projectId' => '\d+'])]
    public function deleteProjectAction(Team $team, #[MapEntity(mapping: ['projectId' => 'id'])] Project $project): Response
    {
        if (!$team->hasProject($project)) {
            throw new BadRequestHttpException('Project is not assigned to the team');
        }

        $team->removeProject($project);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant the team access to an activity
     */
    #[IsGranted('edit_team')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Adds a new activity to a team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team that is granted access', required: true)]
    #[OA\Parameter(name: 'activityId', in: 'path', description: 'The activity to grant acecess to (Activity ID)', required: true)]
    #[Rest\Post(path: '/{id}/activities/{activityId}', name: 'post_team_activity', requirements: ['id' => '\d+', 'activityId' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postActivityAction(Team $team, #[MapEntity(mapping: ['activityId' => 'id'])] Activity $activity): Response
    {
        if ($team->hasActivity($activity)) {
            throw new BadRequestHttpException('Team has already access to activity');
        }

        $team->addActivity($activity);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revokes access for an activity from a team
     */
    #[IsGranted('edit_team')]
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Removes a activity from the team.', content: new OA\JsonContent(ref: '#/components/schemas/Team'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The team whose permission will be revoked', required: true)]
    #[OA\Parameter(name: 'activityId', in: 'path', description: 'The activity to remove (Activity ID)', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}/activities/{activityId}', name: 'delete_team_activity', requirements: ['id' => '\d+', 'activityId' => '\d+'])]
    public function deleteActivityAction(Team $team, #[MapEntity(mapping: ['activityId' => 'id'])] Activity $activity): Response
    {
        if (!$team->hasActivity($activity)) {
            throw new BadRequestHttpException('Activity is not assigned to the team');
        }

        $team->removeActivity($activity);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }
}
