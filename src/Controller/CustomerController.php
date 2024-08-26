<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Customer\CustomerService;
use App\Customer\CustomerStatisticService;
use App\Entity\Customer;
use App\Entity\CustomerComment;
use App\Entity\CustomerRate;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Team;
use App\Event\CustomerDetailControllerEvent;
use App\Event\CustomerMetaDefinitionEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Export\Spreadsheet\EntityWithMetaFieldsExporter;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Form\CustomerCommentForm;
use App\Form\CustomerEditForm;
use App\Form\CustomerRateForm;
use App\Form\CustomerTeamPermissionForm;
use App\Form\Toolbar\CustomerToolbarForm;
use App\Form\Type\CustomerType;
use App\Repository\CustomerRateRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TeamQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TeamRepository;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage customers.
 */
#[Route(path: '/admin/customer')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly EventDispatcherInterface $dispatcher
    )
    {
    }

    #[Route(path: '/', defaults: ['page' => 1], name: 'admin_customer', methods: ['GET'])]
    #[Route(path: '/page/{page}', requirements: ['page' => '[1-9]\d*'], name: 'admin_customer_paginated', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('listing', 'customer')"))]
    public function indexAction(int $page, Request $request): Response
    {
        $query = new CustomerQuery();
        $query->loadTeams();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);

        $form = $this->getToolbarForm($query, $request);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('admin_customer');
        }

        $entries = $this->repository->getPagerfantaForQuery($query);
        $metaColumns = $this->findMetaColumns($query);

        $table = new DataTable('customer_admin', $query);
        $table->setPagination($entries);
        $table->setSearchForm($form);
        $table->setPaginationRoute('admin_customer_paginated');
        $table->setReloadEvents('kimai.customerUpdate kimai.customerDelete kimai.customerTeamUpdate');

        $table->addColumn('name', ['class' => 'alwaysVisible']);
        $table->addColumn('comment', ['class' => 'd-none', 'title' => 'description']);
        $table->addColumn('number', ['class' => 'd-none w-min']);
        $table->addColumn('company', ['class' => 'd-none']);
        $table->addColumn('vat_id', ['class' => 'd-none w-min']);
        $table->addColumn('contact', ['class' => 'd-none']);
        $table->addColumn('address', ['class' => 'd-none']);
        $table->addColumn('country', ['class' => 'd-none w-min']);
        $table->addColumn('currency', ['class' => 'd-none w-min']);
        $table->addColumn('phone', ['class' => 'd-none']);
        $table->addColumn('fax', ['class' => 'd-none']);
        $table->addColumn('mobile', ['class' => 'd-none']);
        $table->addColumn('email', ['class' => 'd-none']);
        $table->addColumn('homepage', ['class' => 'd-none']);

        foreach ($metaColumns as $metaColumn) {
            $table->addColumn('mf_' . $metaColumn->getName(), ['title' => $metaColumn->getLabel(), 'class' => 'd-none', 'orderBy' => false, 'data' => $metaColumn]);
        }

        if ($this->isGranted('budget_money', 'customer')) {
            $table->addColumn('budget', ['class' => 'd-none text-end w-min', 'title' => 'budget']);
        }

        if ($this->isGranted('budget_time', 'customer')) {
            $table->addColumn('timeBudget', ['class' => 'd-none text-end w-min', 'title' => 'timeBudget']);
        }

        $table->addColumn('billable', ['class' => 'd-none text-center w-min', 'orderBy' => false]);
        $table->addColumn('team', ['class' => 'text-center w-min', 'orderBy' => false]);
        $table->addColumn('visible', ['class' => 'd-none text-center w-min']);
        $table->addColumn('actions', ['class' => 'actions']);

        $page = $this->createPageSetup();
        $page->setDataTable($table);
        $page->setActionName('customers');

        return $this->render('customer/index.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
            'metaColumns' => $metaColumns,
            'now' => $this->getDateTimeFactory()->createDateTime(),
        ]);
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    private function findMetaColumns(CustomerQuery $query): array
    {
        $event = new CustomerMetaDisplayEvent($query, CustomerMetaDisplayEvent::CUSTOMER);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    #[Route(path: '/create', name: 'admin_customer_create', methods: ['GET', 'POST'])]
    #[IsGranted('create_customer')]
    public function createAction(Request $request, CustomerService $customerService): Response
    {
        $customer = $customerService->createNewCustomer('');

        return $this->renderCustomerForm($customer, $request, true);
    }

    #[Route(path: '/{id}/permissions', name: 'admin_customer_permissions', methods: ['GET', 'POST'])]
    #[IsGranted('permissions', 'customer')]
    public function teamPermissionsAction(Customer $customer, Request $request): Response
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

                if ($this->isGranted('view', $customer)) {
                    return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
                }

                return $this->redirectToRoute('admin_customer');
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render('customer/permissions.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'customer' => $customer,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/comment_delete/{token}', name: 'customer_comment_delete', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('edit', subject.getCustomer()) and is_granted('comments', subject.getCustomer())"), 'comment')]
    public function deleteCommentAction(CustomerComment $comment, string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $customerId = $comment->getCustomer()->getId();

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('comment.delete', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('customer_details', ['id' => $customerId]);
        }

        $csrfTokenManager->refreshToken('comment.delete');

        try {
            $this->repository->deleteComment($comment);
        } catch (\Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('customer_details', ['id' => $customerId]);
    }

    #[Route(path: '/{id}/comment_add', name: 'customer_comment_add', methods: ['POST'])]
    #[IsGranted('comments', 'customer')]
    public function addCommentAction(Customer $customer, Request $request): Response
    {
        $comment = new CustomerComment($customer);
        $form = $this->getCommentForm($comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->repository->saveComment($comment);
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
    }

    #[Route(path: '/{id}/comment_pin/{token}', name: 'customer_comment_pin', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('edit', subject.getCustomer()) and is_granted('comments', subject.getCustomer())"), 'comment')]
    public function pinCommentAction(CustomerComment $comment, string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $customerId = $comment->getCustomer()->getId();

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('comment.pin', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('customer_details', ['id' => $customerId]);
        }

        $csrfTokenManager->refreshToken('comment.pin');

        $comment->setPinned(!$comment->isPinned());
        try {
            $this->repository->saveComment($comment);
        } catch (\Exception $ex) {
            $this->flashUpdateException($ex);
        }

        return $this->redirectToRoute('customer_details', ['id' => $customerId]);
    }

    #[Route(path: '/{id}/create_team', name: 'customer_team_create', methods: ['GET'])]
    #[IsGranted('create_team')]
    #[IsGranted('permissions', 'customer')]
    public function createDefaultTeamAction(Customer $customer, TeamRepository $teamRepository): Response
    {
        $defaultTeam = $teamRepository->findOneBy(['name' => $customer->getName()]);

        if (null === $defaultTeam) {
            $defaultTeam = new Team($customer->getName());
        }

        $defaultTeam->addTeamlead($this->getUser());
        $defaultTeam->addCustomer($customer);

        try {
            $teamRepository->saveTeam($defaultTeam);
        } catch (\Exception $ex) {
            $this->flashUpdateException($ex);
        }

        return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
    }

    #[Route(path: '/{id}/projects/{page}', defaults: ['page' => 1], name: 'customer_projects', methods: ['GET', 'POST'])]
    #[IsGranted('view', 'customer')]
    public function projectsAction(Customer $customer, int $page, ProjectRepository $projectRepository): Response
    {
        $query = new ProjectQuery();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);
        $query->setPageSize(5);
        $query->addCustomer($customer);
        $query->setShowBoth();
        $query->addOrderGroup('visible', ProjectQuery::ORDER_DESC);
        $query->addOrderGroup('name', ProjectQuery::ORDER_ASC);

        $entries = $projectRepository->getPagerfantaForQuery($query);

        return $this->render('customer/embed_projects.html.twig', [
            'customer' => $customer,
            'projects' => $entries,
            'page' => $page,
            'now' => $this->getDateTimeFactory()->createDateTime(),
        ]);
    }

    #[Route(path: '/{id}/details', name: 'customer_details', methods: ['GET', 'POST'])]
    #[IsGranted('view', 'customer')]
    public function detailsAction(Customer $customer, TeamRepository $teamRepository, CustomerRateRepository $rateRepository, CustomerStatisticService $statisticService): Response
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
        $rates = [];
        $now = $this->getDateTimeFactory()->createDateTime();

        $exportUrl = null;
        $invoiceUrl = null;
        if ($this->isGranted('create_export')) {
            $exportUrl = $this->generateUrl('export', ['customers[]' => $customer->getId(), 'projects[]' => '', 'daterange' => '', 'exported' => TimesheetQuery::STATE_NOT_EXPORTED, 'preview' => true, 'billable' => true]);
        }
        if ($this->isGranted('view_invoice')) {
            $invoiceUrl = $this->generateUrl('invoice', ['customers[]' => $customer->getId(), 'projects[]' => '', 'daterange' => '', 'exported' => TimesheetQuery::STATE_NOT_EXPORTED, 'billable' => true]);
        }

        if ($this->isGranted('edit', $customer)) {
            if ($this->isGranted('create_team')) {
                $defaultTeam = $teamRepository->findOneBy(['name' => $customer->getName()]);
            }
            $rates = $rateRepository->getRatesForCustomer($customer);
        }

        if ($customer->getTimezone() !== null && $customer->getTimezone() !== '') {
            $timezone = new \DateTimeZone($customer->getTimezone());
        }

        if ($this->isGranted('budget', $customer) || $this->isGranted('time', $customer)) {
            $stats = $statisticService->getBudgetStatisticModel($customer, $now);
        }

        if ($this->isGranted('comments', $customer)) {
            $comments = $this->repository->getComments($customer);
            $commentForm = $this->getCommentForm(new CustomerComment($customer))->createView();
        }

        if ($this->isGranted('permissions', $customer) || $this->isGranted('details', $customer) || $this->isGranted('view_team')) {
            $query = new TeamQuery();
            $query->addCustomer($customer);
            $teams = $teamRepository->getTeamsForQuery($query);
        }

        // additional boxes by plugins
        $event = new CustomerDetailControllerEvent($customer);
        $this->dispatcher->dispatch($event);
        $boxes = $event->getController();

        $page = $this->createPageSetup();
        $page->setActionName('customer');
        $page->setActionView('customer_details');
        $page->setActionPayload(['customer' => $customer]);

        return $this->render('customer/details.html.twig', [
            'page_setup' => $page,
            'customer' => $customer,
            'comments' => $comments,
            'commentForm' => $commentForm,
            'attachments' => $attachments,
            'stats' => $stats,
            'team' => $defaultTeam,
            'teams' => $teams,
            'customer_now' => new \DateTime('now', $timezone),
            'rates' => $rates,
            'now' => $now,
            'boxes' => $boxes,
            'export_url' => $exportUrl,
            'invoice_url' => $invoiceUrl,
        ]);
    }

    #[Route(path: '/{id}/rate/{rate}', name: 'admin_customer_rate_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'customer')]
    public function editRateAction(Customer $customer, CustomerRate $rate, Request $request, CustomerRateRepository $repository): Response
    {
        return $this->rateFormAction($customer, $rate, $request, $repository, $this->generateUrl('admin_customer_rate_edit', ['id' => $customer->getId(), 'rate' => $rate->getId()]));
    }

    #[Route(path: '/{id}/rate', name: 'admin_customer_rate_add', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'customer')]
    public function addRateAction(Customer $customer, Request $request, CustomerRateRepository $repository): Response
    {
        $rate = new CustomerRate();
        $rate->setCustomer($customer);

        return $this->rateFormAction($customer, $rate, $request, $repository, $this->generateUrl('admin_customer_rate_add', ['id' => $customer->getId()]));
    }

    private function rateFormAction(Customer $customer, CustomerRate $rate, Request $request, CustomerRateRepository $repository, string $formUrl): Response
    {
        $form = $this->createForm(CustomerRateForm::class, $rate, [
            'action' => $formUrl,
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $repository->saveRate($rate);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render('customer/rates.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'customer' => $customer,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'admin_customer_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'customer')]
    public function editAction(Customer $customer, Request $request): Response
    {
        return $this->renderCustomerForm($customer, $request);
    }

    #[Route(path: '/{id}/delete', name: 'admin_customer_delete', methods: ['GET', 'POST'])]
    #[IsGranted('delete', 'customer')]
    public function deleteAction(Customer $customer, Request $request, CustomerStatisticService $statisticService): Response
    {
        $stats = $statisticService->getCustomerStatistics($customer);

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.customerDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('customer', CustomerType::class, [
                'query_builder_for_user' => true,
                'ignore_customer' => $customer,
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
                $this->flashDeleteException($ex);
            }

            return $this->redirectToRoute('admin_customer');
        }

        return $this->render('customer/delete.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'customer' => $customer,
            'stats' => $stats,
            'form' => $deleteForm->createView(),
        ]);
    }

    #[Route(path: '/export', name: 'customer_export', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('listing', 'customer')"))]
    public function exportAction(Request $request, EntityWithMetaFieldsExporter $exporter): Response
    {
        $query = new CustomerQuery();
        $query->setCurrentUser($this->getUser());

        $form = $this->getToolbarForm($query, $request);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        $entries = $this->repository->getCustomersForQuery($query);

        $spreadsheet = $exporter->export(
            Customer::class,
            $entries,
            new CustomerMetaDisplayEvent($query, CustomerMetaDisplayEvent::EXPORT)
        );
        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-customers');

        return $writer->getFileResponse($spreadsheet);
    }

    private function renderCustomerForm(Customer $customer, Request $request, bool $create = false): Response
    {
        $editForm = $this->createEditForm($customer);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->repository->saveCustomer($customer);
                $this->flashSuccess('action.update.success');

                if ($create) {
                    return $this->redirectToRouteAfterCreate('customer_details', ['id' => $customer->getId()]);
                }

                if ($this->isGranted('view', $customer)) {
                    return $this->redirectToRoute('customer_details', ['id' => $customer->getId()]);
                } else {
                    return new Response();
                }
            } catch (\Exception $ex) {
                $this->handleFormUpdateException($ex, $editForm);
            }
        }

        return $this->render('customer/edit.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'customer' => $customer,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @return FormInterface<CustomerQuery>
     */
    private function getToolbarForm(CustomerQuery $query, Request $request): FormInterface
    {
        return $this->createSearchForm(CustomerToolbarForm::class, $query, [
            'locale' => $request->getLocale(),
            'action' => $this->generateUrl('admin_customer', [
                'page' => $query->getPage(),
            ])
        ]);
    }

    /**
     * @return FormInterface<mixed>
     */
    private function getCommentForm(CustomerComment $comment): FormInterface
    {
        if (null === $comment->getId()) {
            $comment->setCreatedBy($this->getUser());
        }

        return $this->createForm(CustomerCommentForm::class, $comment, [
            'action' => $this->generateUrl('customer_comment_add', ['id' => $comment->getCustomer()->getId()]),
            'method' => 'POST',
        ]);
    }

    /**
     * @return FormInterface<Customer>
     */
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
            'include_budget' => $this->isGranted('budget', $customer),
            'include_time' => $this->isGranted('time', $customer),
        ]);
    }

    private function createPageSetup(): PageSetup
    {
        $page = new PageSetup('customers');
        $page->setHelp('customer.html');

        return $page;
    }
}
