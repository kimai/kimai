<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 2019-02-06
 * Time: 12:49
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
//        if ($)

        return $this->json($tags, 200);
    }
}