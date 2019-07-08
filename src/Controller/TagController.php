<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\Toolbar\TagToolbarForm;
use App\Repository\Query\TagQuery;
use App\Repository\TagRepository;
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
