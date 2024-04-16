<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Form\MultiUpdate\MultiUpdateTable;
use App\Form\MultiUpdate\MultiUpdateTableDTO;
use App\Form\TagEditForm;
use App\Form\Toolbar\TagToolbarForm;
use App\Repository\Query\TagQuery;
use App\Repository\TagRepository;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/tags')]
#[IsGranted('view_tag')]
final class TagController extends AbstractController
{
    /**
     * @param TagRepository $repository
     * @param Request $request
     * @param int $page
     * @return Response
     */
    #[Route(path: '/', defaults: ['page' => 1], name: 'tags', methods: ['GET'])]
    #[Route(path: '/page/{page}', requirements: ['page' => '[1-9]\d*'], name: 'tags_paginated', methods: ['GET'])]
    public function listTags(TagRepository $repository, Request $request, $page): Response
    {
        $query = new TagQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('tags');
        }

        $entries = $repository->getTagCount($query);
        $multiUpdateForm = $this->getMultiUpdateForm($repository);

        $table = new DataTable('admin_tags', $query);
        $table->setSearchForm($form);
        $table->setPagination($entries);
        $table->setPaginationRoute('tags_paginated');
        $table->setReloadEvents('kimai.tagUpdate');
        $table->setBatchForm($multiUpdateForm);

        if ($multiUpdateForm !== null) {
            $table->addColumn('id', ['class' => 'alwaysVisible multiCheckbox', 'orderBy' => false, 'title' => false, 'batchUpdate' => true]);
        }

        $table->addColumn('name', ['class' => 'alwaysVisible']);
        $table->addColumn('amount', ['class' => 'text-center w-min']);
        $table->addColumn('visible', ['class' => 'd-none text-center w-min']);
        $table->addColumn('actions', ['class' => 'actions']);

        $page = new PageSetup('tags');
        $page->setActionName('tags');
        $page->setHelp('tags.html');
        $page->setDataTable($table);

        return $this->render('tags/index.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'tags_edit', methods: ['GET', 'POST'])]
    #[IsGranted('manage_tag')]
    public function editAction(Tag $tag, TagRepository $repository, Request $request): Response
    {
        $editForm = $this->createForm(TagEditForm::class, $tag, [
            'action' => $this->generateUrl('tags_edit', ['id' => $tag->getId()]),
            'method' => 'POST',
        ]);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $repository->saveTag($tag);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('tags');
            } catch (\Exception $ex) {
                $this->handleFormUpdateException($ex, $editForm);
            }
        }

        $page = new PageSetup('tags');
        $page->setHelp('tags.html');

        return $this->render('tags/edit.html.twig', [
            'page_setup' => $page,
            'tag' => $tag,
            'form' => $editForm->createView()
        ]);
    }

    #[Route(path: '/create', name: 'tags_create', methods: ['GET', 'POST'])]
    #[IsGranted('manage_tag')]
    public function createAction(TagRepository $repository, Request $request): Response
    {
        $tag = new Tag();

        $editForm = $this->createForm(TagEditForm::class, $tag, [
            'action' => $this->generateUrl('tags_create'),
            'method' => 'POST',
        ]);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $repository->saveTag($tag);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('tags');
            } catch (\Exception $ex) {
                $this->handleFormUpdateException($ex, $editForm);
            }
        }

        $page = new PageSetup('tags');
        $page->setHelp('tags.html');

        return $this->render('tags/edit.html.twig', [
            'page_setup' => $page,
            'tag' => $tag,
            'form' => $editForm->createView()
        ]);
    }

    #[Route(path: '/multi-delete', name: 'tags_multi_delete', methods: ['POST'])]
    #[IsGranted('delete_tag')]
    public function multiDelete(TagRepository $repository, Request $request): Response
    {
        $form = $this->getMultiUpdateForm($repository);

        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    /** @var MultiUpdateTableDTO $dto */
                    $dto = $form->getData();
                    $repository->multiDelete($dto->getEntities());
                    $this->flashSuccess('action.delete.success');
                } catch (\Exception $ex) {
                    $this->flashDeleteException($ex);
                }
            }
        }

        return $this->redirectToRoute('tags');
    }

    #[Route(path: '/multi-invisible', name: 'tags_multi_invisible', methods: ['POST'])]
    #[IsGranted('manage_tag')]
    public function multiInvisible(TagRepository $repository, Request $request): Response
    {
        return $this->multiUpdateVisible($repository, $request, false);
    }

    #[Route(path: '/multi-visible', name: 'tags_multi_visible', methods: ['POST'])]
    #[IsGranted('manage_tag')]
    public function multiVisible(TagRepository $repository, Request $request): Response
    {
        return $this->multiUpdateVisible($repository, $request, true);
    }

    private function multiUpdateVisible(TagRepository $repository, Request $request, bool $visible): Response
    {
        $form = $this->getMultiUpdateForm($repository);

        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    /** @var MultiUpdateTableDTO $dto */
                    $dto = $form->getData();
                    /** @var Tag $tag */
                    foreach ($dto->getEntities() as $tag) {
                        $tag->setVisible($visible);
                    }
                    $repository->multiUpdate($dto->getEntities());
                    $this->flashSuccess('action.delete.success');
                } catch (\Exception $ex) {
                    $this->flashDeleteException($ex);
                }
            }
        }

        return $this->redirectToRoute('tags');
    }

    private function getMultiUpdateForm(TagRepository $repository): ?FormInterface
    {
        $dto = new MultiUpdateTableDTO();

        if ($this->isGranted('manage_tag')) {
            $dto->addAction('visible', $this->generateUrl('tags_multi_visible'));
            $dto->addAction('invisible', $this->generateUrl('tags_multi_invisible'));
        }

        if ($this->isGranted('delete_tag')) {
            $dto->addDelete($this->generateUrl('tags_multi_delete'));
        }

        if (!$dto->hasAction()) {
            return null;
        }

        return $this->createForm(MultiUpdateTable::class, $dto, [
            'action' => $this->generateUrl('tags'),
            'repository' => $repository,
            'method' => 'POST',
        ]);
    }

    private function getToolbarForm(TagQuery $query): FormInterface
    {
        return $this->createSearchForm(TagToolbarForm::class, $query, [
            'action' => $this->generateUrl('tags', [
                'page' => $query->getPage(),
            ]),
        ]);
    }
}
