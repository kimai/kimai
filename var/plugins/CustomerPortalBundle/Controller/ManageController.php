<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Controller;

use App\Controller\AbstractController;
use App\Repository\Query\BaseQuery;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\CustomerPortalBundle\Form\SharedCustomerFormType;
use KimaiPlugin\CustomerPortalBundle\Form\SharedProjectFormType;
use KimaiPlugin\CustomerPortalBundle\Model\RecordMergeMode;
use KimaiPlugin\CustomerPortalBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\CustomerPortalBundle\Service\ManageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/customer-portal')]
#[IsGranted('customer_portal')]
class ManageController extends AbstractController
{
    public function __construct(
        private readonly SharedProjectTimesheetRepository $shareProjectTimesheetRepository,
        private readonly ManageService $manageService
    ) {
    }

    #[Route(path: '', name: 'manage_shared_project_timesheets', methods: ['GET'])]
    public function index(): Response
    {
        $query = new BaseQuery();

        $sharedProjects = $this->shareProjectTimesheetRepository->findAllSharedProjects($query);

        $table = new DataTable('shared_project_timesheets_manage', $query);
        $table->setPagination($sharedProjects);
        $table->setReloadEvents('kimai.sharedProject');

        $table->addColumn('type', ['class' => 'alwaysVisible w-min', 'orderBy' => false]);
        $table->addColumn('name', ['class' => 'alwaysVisible', 'orderBy' => false]);
        $table->addColumn('url', ['class' => 'alwaysVisible', 'orderBy' => false]);
        $table->addColumn('password', ['class' => 'd-none', 'orderBy' => false]);
        $table->addColumn('record_merge_mode', ['class' => 'd-none text-center w-min', 'orderBy' => false, 'title' => 'shared_project_timesheets.manage.table.record_merge_mode']);
        $table->addColumn('entry_user_visible', ['class' => 'd-none text-center w-min', 'orderBy' => false, 'title' => 'shared_project_timesheets.manage.table.entry_user_visible']);
        $table->addColumn('entry_rate_visible', ['class' => 'd-none text-center w-min', 'orderBy' => false, 'title' => 'shared_project_timesheets.manage.table.entry_rate_visible']);
        $table->addColumn('annual_chart_visible', ['class' => 'd-none text-center w-min', 'orderBy' => false, 'title' => 'shared_project_timesheets.manage.table.annual_chart_visible']);
        $table->addColumn('monthly_chart_visible', ['class' => 'd-none text-center w-min', 'orderBy' => false, 'title' => 'shared_project_timesheets.manage.table.monthly_chart_visible']);

        $table->addColumn('actions', ['class' => 'actions alwaysVisible']);

        $page = new PageSetup('shared_project_timesheets.title');
        $page->setActionName('customer_portal');
        $page->setDataTable($table);
        $page->setHelp('plugin-customer-portal.html');

        return $this->render('@CustomerPortal/manage/index.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
            'RecordMergeMode' => RecordMergeMode::getModes(),
        ]);
    }

    #[Route(path: '/create', name: 'create_shared_project_timesheets', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $type = $request->query->get('type');

        if (!\in_array($type, [SharedProjectTimesheet::TYPE_CUSTOMER, SharedProjectTimesheet::TYPE_PROJECT], true)) {
            return $this->redirectToRoute('manage_shared_project_timesheets');
        }

        $sharedProject = new SharedProjectTimesheet();

        $formClass = $type === SharedProjectTimesheet::TYPE_CUSTOMER ?
            SharedCustomerFormType::class :
            SharedProjectFormType::class;

        $form = $this->createForm($formClass, $sharedProject, [
            'method' => 'POST',
            'action' => $this->generateUrl('create_shared_project_timesheets', ['type' => $type]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var string $password */
                $password = $form->get('password')->getData();
                $this->manageService->create($sharedProject, $password);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('manage_shared_project_timesheets');
            } catch (\Exception $e) {
                $this->flashUpdateException($e);
            }
        }

        return $this->render('@CustomerPortal/manage/edit.html.twig', [
            'entity' => $sharedProject,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{sharedProject}/{shareKey}', name: 'update_shared_project_timesheets', methods: ['GET', 'POST'])]
    public function update(SharedProjectTimesheet $sharedProject, string $shareKey, Request $request): Response
    {
        if ($sharedProject->getShareKey() !== $shareKey) {
            throw $this->createNotFoundException('Project not found');
        }

        $formClass = SharedProjectFormType::class;
        if ($sharedProject->getCustomer() !== null) {
            $formClass = SharedCustomerFormType::class;
        }

        $form = $this->createForm($formClass, $sharedProject, [
            'method' => 'POST',
            'action' => $this->generateUrl('update_shared_project_timesheets', ['sharedProject' => $sharedProject->getId(), 'shareKey' => $shareKey])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var string $password */
                $password = $form->get('password')->getData();
                $this->manageService->update($sharedProject, $password);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('manage_shared_project_timesheets');
            } catch (\Exception $e) {
                $this->flashUpdateException($e);
            }
        } elseif (!$form->isSubmitted()) {
            if ($sharedProject->hasPassword()) {
                $form->get('password')->setData(ManageService::PASSWORD_DO_NOT_CHANGE_VALUE);
            }
        }

        return $this->render('@CustomerPortal/manage/edit.html.twig', [
            'entity' => $sharedProject,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{sharedProject}/{shareKey}/remove', name: 'remove_shared_project_timesheets', methods: ['GET', 'POST'])]
    public function remove(SharedProjectTimesheet $sharedProject, string $shareKey): Response
    {
        if ($sharedProject->getShareKey() !== $shareKey) {
            throw $this->createNotFoundException('Project not found');
        }

        try {
            $this->shareProjectTimesheetRepository->remove($sharedProject);
            $this->flashSuccess('action.delete.success');
        } catch (\Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('manage_shared_project_timesheets');
    }
}
