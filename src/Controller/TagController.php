<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Form\TagEditForm;
use App\Form\Toolbar\TagToolbarForm;
use App\Repository\Query\TagQuery;
use App\Repository\TagRepository;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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

        $tags = $repository->getTagCount($query);

        return $this->render('tags/index.html.twig', [
            'tags' => $tags,
            'query' => $query,
            'showFilter' => $query->isDirty(),
            'toolbarForm' => $form->createView(),
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
