<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Bookmark;
use App\Repository\BookmarkRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * This does not go into the API, because it is ONLY related to the Web UI.
 *
 * @Route(path="/bookmark")
 */
class BookmarkController extends AbstractController
{
    public const DATATABLE_TOKEN = 'datatable_update';

    public function __construct(private BookmarkRepository $bookmarkRepository)
    {
    }

    /**
     * @Route(path="/datatable/save", name="bookmark_save_datatable", methods={"POST"})
     */
    public function datatableSave(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$request->request->has('datatable_token') || !$request->request->has('datatable_name')) {
            throw $this->createNotFoundException('Missing CSRF Token');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::DATATABLE_TOKEN, $request->request->get('datatable_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF Token');
        }

        $datatableName = $request->request->get('datatable_name');

        if (empty($datatableName) || mb_strlen($datatableName) > 50) {
            throw $this->createAccessDeniedException('Invalid datatable name');
        }

        $enabled = [];
        foreach ($request->request->all() as $name => $value) {
            if ($value !== 'on') {
                continue;
            }
            $enabled[$name] = true;
        }

        $user = $this->getUser();

        $bookmark = $this->bookmarkRepository->findBookmark($user, 'datatable', $datatableName);
        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setType('datatable');
            $bookmark->setName($datatableName);
        }

        $bookmark->setContent($enabled);

        $this->bookmarkRepository->saveBookmark($bookmark);

        $csrfTokenManager->refreshToken(self::DATATABLE_TOKEN);

        return new Response();
    }

    /**
     * @Route(path="/datatable/delete", name="bookmark_delete", methods={"POST"})
     */
    public function datatableDelete(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$request->request->has('datatable_token') || !$request->request->has('datatable_name')) {
            throw $this->createNotFoundException('Missing CSRF Token');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::DATATABLE_TOKEN, $request->request->get('datatable_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF Token');
        }

        $datatableName = $request->request->get('datatable_name');

        $bookmark = $this->bookmarkRepository->findBookmark($this->getUser(), 'datatable', $datatableName);
        if ($bookmark !== null) {
            $this->bookmarkRepository->deleteBookmark($bookmark);
        }

        $csrfTokenManager->refreshToken(self::DATATABLE_TOKEN);

        return new Response();
    }
}
