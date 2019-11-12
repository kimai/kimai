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
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/tags")
 * @Security("is_granted('view_tag')")
 */
class TagController extends AbstractController
{
    /**
     * @Route(path="/", defaults={"page": 1}, name="tags", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="tags_paginated", methods={"GET"})
     *
     * @param TagRepository $repository
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function listTags(TagRepository $repository, Request $request, $page)
    {
        $query = new TagQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        $tags = $repository->getTagCount($query);

        return $this->render('tags/index.html.twig', [
            'tags' => $tags,
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'multiUpdateForm' => $this->getMultiUpdateForm($repository)->createView(),
        ]);
    }

    /**
     * @Route(path="/{id}/edit", name="tags_edit", methods={"GET", "POST"})
     * @Security("is_granted('manage_tag')")
     */
    public function editAction(Tag $tag, TagRepository $repository, Request $request)
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
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('tags/edit.html.twig', [
            'tag' => $tag,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @Route(path="/create", name="tags_create", methods={"GET", "POST"})
     * @Security("is_granted('manage_tag')")
     */
    public function createAction(TagRepository $repository, Request $request)
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
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('tags/edit.html.twig', [
            'tag' => $tag,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @Route(path="/multi-delete", name="tags_multi_delete", methods={"POST"})
     * @Security("is_granted('delete_tag')")
     */
    public function multiDelete(TagRepository $repository, Request $request)
    {
        $form = $this->getMultiUpdateForm($repository);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var MultiUpdateTableDTO $dto */
                $dto = $form->getData();
                $repository->multiDelete($dto->getEntities());
                $this->flashSuccess('action.delete.success');
            } catch (\Exception $ex) {
                $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->redirectToRoute('tags');
    }

    protected function getMultiUpdateForm(TagRepository $repository): FormInterface
    {
        $dto = new MultiUpdateTableDTO();
        $dto->addDelete($this->generateUrl('tags_multi_delete'));

        return $this->createForm(MultiUpdateTable::class, $dto, [
            'action' => $this->generateUrl('tags'),
            'repository' => $repository,
            'method' => 'POST',
        ]);
    }

    /**
     * @param TagQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(TagQuery $query)
    {
        return $this->createForm(TagToolbarForm::class, $query, [
            'action' => $this->generateUrl('tags', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }
}
