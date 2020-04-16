<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\FormConfiguration;
use App\Entity\Customer;
use App\Entity\CustomerComment;
use App\Entity\CustomerRate;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Rate;
use App\Entity\Team;
use App\Event\CustomerMetaDefinitionEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Form\CustomerCommentForm;
use App\Form\CustomerEditForm;
use App\Form\CustomerRateForm;
use App\Form\CustomerTeamPermissionForm;
use App\Form\Toolbar\CustomerToolbarForm;
use App\Form\Type\CustomerType;
use App\Repository\CustomerRateRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\TeamRepository;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage customer in the admin part of the site.
 *
 * @Route(path="/admin/customer")
 * @Security("is_granted('view_customer') or is_granted('view_teamlead_customer') or is_granted('view_team_customer')")
 */
final class CustomerController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    private $repository;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(CustomerRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_customer", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_customer_paginated", methods={"GET"})
     */
    public function indexAction($page, Request $request)
    {
        $query = new CustomerQuery();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        $entries = $this->repository->getPagerfantaForQuery($query);

        return $this->render('customer/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'metaColumns' => $this->findMetaColumns($query),
        ]);
    }

    /**
     * @param CustomerQuery $query
     * @return MetaTableTypeInterface[]
     */
    private function findMetaColumns(CustomerQuery $query): array
    {
        $event = new CustomerMetaDisplayEvent($query, CustomerMetaDisplayEvent::CUSTOMER);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    /**
     * @Route(path="/create", name="admin_customer_create", methods={"GET", "POST"})
     * @Security("is_granted('create_customer')")
     */
    public function createAction(Request $request, FormConfiguration $configuration)
    {
        $timezone = date_default_timezone_get();
        if (null !== $configuration->getCustomerDefaultTimezone()) {
            $timezone = $configuration->getCustomerDefaultTimezone();
        }

        $customer = new Customer();
        $customer->setCountry($configuration->getCustomerDefaultCountry());
        $customer->setCurrency($configuration->getCustomerDefaultCurrency());
        $customer->setTimezone($timezone);

        return $this->renderCustomerForm($customer, $request);
    }

    /**
     * @Route(path="/{id}/permissions", name="admin_customer_permissions", methods={"GET", "POST"})
     * @Security("is_granted('permissions', customer)")
     */
    public function teamPermissionsAction(Customer $customer, Request $request)
    {
        $form = $this->createForm(CustomerTeamPermissionForm::class, $customer, [
            'action' => $this->generateUrl('admin_customer_permissions', ['id' => $customer->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->repository->saveCustomer($customer);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_customer');
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('customer/permissions.html.twig', [
            'customer' => $customer,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/{id}/comment_delete", name="customer_comment_delete", methods={"GET"})
     * @Security("is_granted('edit', comment.getCustomer()) and is_granted('comments', comment.getCustomer())")
     */
    public function deleteCommentAction(CustomerComment $comment)
    {
        $customerId = $comment->getCustomer()->getId();

        try {
            $this->repository->deleteComment($comment);
        } catch (\Exception $ex) {
            $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('customer_details', ['id' => $customerId]);
    }

    /**
     * @Route(path="/{id}/comment_add", name="customer_comment_add", methods={"POST"})
     * @Security("is_granted('comments_create', customer)")
     */
    public function addCommentAction(Customer $customer, Request $request)
    {
        $comment = new CustomerComment();
        $form = $this->getCommentForm($customer, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->repository->saveComment($comment);
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
    }

    /**
     * @Route(path="/{id}/comment_pin", name="customer_comment_pin", methods={"GET"})
     * @Security("is_granted('edit', comment.getCustomer()) and is_granted('comments', comment.getCustomer())")
     */
    public function pinCommentAction(CustomerComment $comment)
    {
        $comment->setPinned(!$comment->isPinned());
        try {
            $this->repository->saveComment($comment);
        } catch (\Exception $ex) {
            $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('customer_details', ['id' => $comment->getCustomer()->getId()]);
    }

    /**
     * @Route(path="/{id}/create_team", name="customer_team_create", methods={"GET"})
     * @Security("is_granted('create_team') and is_granted('permissions', customer)")
     */
    public function createDefaultTeamAction(Customer $customer, TeamRepository $teamRepository)
    {
        $defaultTeam = $teamRepository->findOneBy(['name' => $customer->getName()]);
        if (null !== $defaultTeam) {
            $this->flashError('action.update.error', ['%reason%' => 'Team already existing']);

            return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
        }

        $defaultTeam = new Team();
        $defaultTeam->setName($customer->getName());
        $defaultTeam->setTeamLead($this->getUser());
        $defaultTeam->addCustomer($customer);

        try {
            $teamRepository->saveTeam($defaultTeam);
        } catch (\Exception $ex) {
            $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
    }

    /**
     * @Route(path="/{id}/projects/{page}", defaults={"page": 1}, name="customer_projects", methods={"GET", "POST"})
     * @Security("is_granted('view', customer)")
     */
    public function projectsAction(Customer $customer, int $page, ProjectRepository $projectRepository)
    {
        $query = new ProjectQuery();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);
        $query->setPageSize(5);
        $query->addCustomer($customer);

        /* @var $entries Pagerfanta */
        $entries = $projectRepository->getPagerfantaForQuery($query);

        return $this->render('customer/embed_projects.html.twig', [
            'customer' => $customer,
            'projects' => $entries,
            'page' => $page,
        ]);
    }

    /**
     * @Route(path="/{id}/details", name="customer_details", methods={"GET", "POST"})
     * @Security("is_granted('view', customer)")
     */
    public function detailsAction(Customer $customer, TeamRepository $teamRepository, CustomerRateRepository $rateRepository)
    {
        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch($event);

        $stats = null;
        $timezone = null;
        $defaultTeam = null;
        $commentForm = null;
        $attachments = [];
        $comments = null;
        $teams = null;
        $projects = null;
        $rates = [];

        if ($this->isGranted('edit', $customer)) {
            if ($this->isGranted('create_team')) {
                $defaultTeam = $teamRepository->findOneBy(['name' => $customer->getName()]);
            }
            $rates = $rateRepository->getRatesForCustomer($customer);
        }

        if (null !== $customer->getTimezone()) {
            $timezone = new \DateTimeZone($customer->getTimezone());
        }

        if ($this->isGranted('budget', $customer)) {
            $stats = $this->repository->getCustomerStatistics($customer);
        }

        if ($this->isGranted('comments', $customer)) {
            $comments = $this->repository->getComments($customer);
        }

        if ($this->isGranted('comments_create', $customer)) {
            $commentForm = $this->getCommentForm($customer, new CustomerComment())->createView();
        }

        if ($this->isGranted('permissions', $customer) || $this->isGranted('details', $customer) || $this->isGranted('view_team')) {
            $teams = $customer->getTeams();
        }

        return $this->render('customer/details.html.twig', [
            'customer' => $customer,
            'comments' => $comments,
            'commentForm' => $commentForm,
            'attachments' => $attachments,
            'stats' => $stats,
            'team' => $defaultTeam,
            'teams' => $teams,
            'now' => new \DateTime('now', $timezone),
            'rates' => $rates
        ]);
    }

    /**
     * @Route(path="/{id}/rate", name="admin_customer_rate_add", methods={"GET", "POST"})
     * @Security("is_granted('edit', customer)")
     */
    public function addRateAction(Customer $customer, Request $request, CustomerRateRepository $repository)
    {
        $rate = new CustomerRate();
        $rate->setCustomer($customer);

        $form = $this->createForm(CustomerRateForm::class, $rate, [
            'action' => $this->generateUrl('admin_customer_rate_add', ['id' => $customer->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $repository->saveRate($rate);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('customer/rates.html.twig', [
            'customer' => $customer,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_customer_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', customer)")
     */
    public function editAction(Customer $customer, Request $request)
    {
        return $this->renderCustomerForm($customer, $request);
    }

    /**
     * @Route(path="/{id}/delete", name="admin_customer_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', customer)")
     */
    public function deleteAction(Customer $customer, Request $request)
    {
        $stats = $this->repository->getCustomerStatistics($customer);

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.customerUpdate kimai.customerDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
                'query_builder' => function (CustomerRepository $repo) use ($customer) {
                    $query = new CustomerFormTypeQuery();
                    $query->setCustomerToIgnore($customer);
                    $query->setUser($this->getUser());

                    return $repo->getQueryBuilderForFormType($query);
                },
                'required' => false,
            ])
            ->setAction($this->generateUrl('admin_customer_delete', ['id' => $customer->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            try {
                $this->repository->deleteCustomer($customer, $deleteForm->get('customer')->getData());
                $this->flashSuccess('action.delete.success');
            } catch (\Exception $ex) {
                $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute('admin_customer');
        }

        return $this->render('customer/delete.html.twig', [
            'customer' => $customer,
            'stats' => $stats,
            'form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @param Customer $customer
     * @param Request $request
     * @return RedirectResponse|Response
     */
    private function renderCustomerForm(Customer $customer, Request $request)
    {
        $editForm = $this->createEditForm($customer);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->repository->saveCustomer($customer);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $editForm->createView()
        ]);
    }

    private function getToolbarForm(CustomerQuery $query): FormInterface
    {
        return $this->createForm(CustomerToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_customer', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    private function getCommentForm(Customer $customer, CustomerComment $comment): FormInterface
    {
        if (null === $comment->getId()) {
            $comment->setCustomer($customer);
            $comment->setCreatedBy($this->getUser());
        }

        return $this->createForm(CustomerCommentForm::class, $comment, [
            'action' => $this->generateUrl('customer_comment_add', ['id' => $customer->getId()]),
            'method' => 'POST',
        ]);
    }

    private function createEditForm(Customer $customer): FormInterface
    {
        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch($event);

        if ($customer->getId() === null) {
            $url = $this->generateUrl('admin_customer_create');
        } else {
            $url = $this->generateUrl('admin_customer_edit', ['id' => $customer->getId()]);
        }

        return $this->createForm(CustomerEditForm::class, $customer, [
            'action' => $url,
            'method' => 'POST',
            'include_budget' => $this->isGranted('budget', $customer)
        ]);
    }
}
