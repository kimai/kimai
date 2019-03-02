<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TagUtilityController extends AbstractController
{
    /**
     * @Route("/tag/names", methods="GET", name="tag_names")
     * @IsGranted("ROLE_USER")
     * @param TagRepository $tagRepository
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTagNames(TagRepository $tagRepository, Request $request)
    {
        $tags = $tagRepository->findAllTagNamesAlphabetical($request->get('term'));

        return $this->json($tags, 200);
    }
}
