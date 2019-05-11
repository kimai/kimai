<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/tags")
 * @Security("is_granted('view_tag')")
 */
class TagController extends AbstractController
{
    /**
     * @Route(path="/", name="tags", methods={"GET"})
     *
     * @param TagRepository $repository
     * @return Response
     */
    public function listTags(TagRepository $repository)
    {
        $tags = $repository->getTagCount();

        return $this->render('tags/index.html.twig', [
            'tags' => $tags,
        ]);
    }
}
