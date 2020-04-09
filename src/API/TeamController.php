<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Form\API\TeamApiEditForm;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RouteResource("Team")
 * @SWG\Tag(name="Team")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
final class TeamController extends BaseApiController
{
    /**
     * @var TeamRepository
     */
    private $repository;
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    public function __construct(ViewHandlerInterface $viewHandler, TeamRepository $repository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
    }

    /**
     * Fetch all existing teams
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all existing teams",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TeamCollection")
     *      )
     * )
     *
     * @Security("is_granted('view_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $data = $this->repository->findAll();

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Team']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one team
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one team entity",
     *      @SWG\Schema(ref="#/definitions/TeamEntity"),
     * )
     *
     * @Security("is_granted('view_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function getAction(int $id): Response
    {
        $data = $this->repository->find($id);

        if (null === $data) {
            throw new NotFoundException();
        }

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete a team
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=204,
     *          description="Delete one team"
     *      ),
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Team ID to delete",
     *      required=true,
     * )
     *
     * @Security("is_granted('delete_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteAction(int $id): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException();
        }

        $this->repository->deleteTeam($team);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new team
     *
     * @SWG\Post(
     *      description="Creates a new team and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created team",
     *          @SWG\Schema(ref="#/definitions/TeamEntity",),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/TeamEditForm")
     * )
     *
     * @Security("is_granted('create_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $team = new Team();
        $team->setTeamLead($user);

        $form = $this->createForm(TeamApiEditForm::class, $team);

        $form->submit($request->request->all());
        $team->addUser($team->getTeamLead());

        if ($form->isValid()) {
            $this->repository->saveTeam($team);

            $view = new View($team, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing team
     *
     * @SWG\Patch(
     *      description="Update an existing team, you can pass all or just a subset of all attributes (passing users will replace all existing ones)",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated team",
     *          @SWG\Schema(ref="#/definitions/TeamEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/TeamEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Team ID to update",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function patchAction(Request $request, int $id): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException();
        }

        $form = $this->createForm(TeamApiEditForm::class, $team);

        $form->setData($team);
        $form->submit($request->request->all(), false);
        $team->addUser($team->getTeamLead());

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Add a new member to a team
     *
     * @SWG\Post(
     *  @SWG\Response(
     *      response=200,
     *      description="Adds a new user to a team. The user must not be deactivated.",
     *      @SWG\Schema(ref="#/definitions/TeamEntity")
     *  )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The team which will receive the new member",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="userId",
     *      in="path",
     *      type="integer",
     *      description="The team member to add (User ID)",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postMemberAction(int $id, int $userId, UserRepository $repository): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException('Team not found');
        }

        /** @var User|null $user */
        $user = $repository->find($userId);

        if (null === $user) {
            throw new NotFoundException('User not found');
        }

        if (!$user->isEnabled()) {
            throw new BadRequestHttpException('Cannot add disabled user to team');
        }

        if ($user->isInTeam($team)) {
            throw new BadRequestHttpException('User is already member of the team');
        }

        $team->addUser($user);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Removes a member from the team
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=200,
     *          description="Removes a user from the team. The teamlead cannot be removed.",
     *          @SWG\Schema(ref="#/definitions/TeamEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The team from which the member will be removed",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="userId",
     *      in="path",
     *      type="integer",
     *      description="The team member to remove (User ID)",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteMemberAction(int $id, int $userId, UserRepository $repository): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException('Team not found');
        }

        /** @var User|null $user */
        $user = $repository->find($userId);

        if (null === $user) {
            throw new NotFoundException('User not found');
        }

        if (!$user->isInTeam($team)) {
            throw new BadRequestHttpException('User is not a member of the team');
        }

        if ($team->isTeamlead($user)) {
            throw new BadRequestHttpException('Cannot remove teamlead');
        }

        $team->removeUser($user);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant the team access to a customer
     *
     * @SWG\Post(
     *  @SWG\Response(
     *      response=200,
     *      description="Adds a new customer to a team. The customer must not be invisible.",
     *      @SWG\Schema(ref="#/definitions/TeamEntity")
     *  )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The team that is granted access",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="customerId",
     *      in="path",
     *      type="integer",
     *      description="The customer to grant acecess to (Customer ID)",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postCustomerAction(int $id, int $customerId, CustomerRepository $repository): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException('Team not found');
        }

        /** @var Customer|null $customer */
        $customer = $repository->find($customerId);

        if (null === $customer) {
            throw new NotFoundException('Customer not found');
        }

        if (!$customer->isVisible()) {
            throw new BadRequestHttpException('Cannot grant access to an invisible customer');
        }

        if ($team->hasCustomer($customer)) {
            throw new BadRequestHttpException('Team has already access to customer');
        }

        $team->addCustomer($customer);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revokes access for a customer from a team
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=200,
     *          description="Removes a customer from the team.",
     *          @SWG\Schema(ref="#/definitions/TeamEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The team whose permission will be revoked",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="customerId",
     *      in="path",
     *      type="integer",
     *      description="The customer to remove (Customer ID)",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteCustomerAction(int $id, int $customerId, CustomerRepository $repository): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException('Team not found');
        }

        /** @var Customer|null $customer */
        $customer = $repository->find($customerId);

        if (null === $customer) {
            throw new NotFoundException('Customer not found');
        }

        if (!$team->hasCustomer($customer)) {
            throw new BadRequestHttpException('Customer is not assigned to the team');
        }

        $team->removeCustomer($customer);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Grant the team access to a project
     *
     * @SWG\Post(
     *  @SWG\Response(
     *      response=200,
     *      description="Adds a new project to a team. The project must not be invisible.",
     *      @SWG\Schema(ref="#/definitions/TeamEntity")
     *  )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The team that is granted access",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="projectId",
     *      in="path",
     *      type="integer",
     *      description="The project to grant acecess to (Project ID)",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postProjectAction(int $id, int $projectId, ProjectRepository $repository): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException('Team not found');
        }

        /** @var Project|null $project */
        $project = $repository->find($projectId);

        if (null === $project) {
            throw new NotFoundException('Project not found');
        }

        if (!$project->isVisible()) {
            throw new BadRequestHttpException('Cannot grant access to an invisible project');
        }

        if ($team->hasProject($project)) {
            throw new BadRequestHttpException('Team has already access to project');
        }

        $team->addProject($project);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Revokes access for a project from a team
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=200,
     *          description="Removes a project from the team.",
     *          @SWG\Schema(ref="#/definitions/TeamEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The team whose permission will be revoked",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="projectId",
     *      in="path",
     *      type="integer",
     *      description="The project to remove (Project ID)",
     *      required=true,
     * )
     *
     * @Security("is_granted('edit_team')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteProjectAction(int $id, int $projectId, ProjectRepository $repository): Response
    {
        $team = $this->repository->find($id);

        if (null === $team) {
            throw new NotFoundException('Team not found');
        }

        /** @var Project|null $project */
        $project = $repository->find($projectId);

        if (null === $project) {
            throw new NotFoundException('Project not found');
        }

        if (!$team->hasProject($project)) {
            throw new BadRequestHttpException('Project is not assigned to the team');
        }

        $team->removeProject($project);

        $this->repository->saveTeam($team);

        $view = new View($team, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Team_Entity']);

        return $this->viewHandler->handle($view);
    }
}
