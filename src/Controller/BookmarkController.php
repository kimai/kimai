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
use App\Utils\ProfileManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * This does not go into the API, because it is ONLY related to the Web UI.
 */
#[Route(path: '/bookmark')]
final class BookmarkController extends AbstractController
{
    public const DATATABLE_TOKEN = 'datatable_update';
    public const PARAM_TOKEN_NAME = 'datatable_token';
    public const PARAM_DATATABLE = 'datatable_name';
    public const PARAM_PROFILE = 'datatable_profile';

    public function __construct(private BookmarkRepository $bookmarkRepository, private ProfileManager $profileManager)
    {
    }

    #[Route(path: '/datatable/profile', name: 'bookmark_profile', methods: ['POST'])]
    public function datatableProfile(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$request->request->has(self::PARAM_TOKEN_NAME) || !$request->request->has(self::PARAM_PROFILE)) {
            throw $this->createNotFoundException('Missing CSRF Token');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::DATATABLE_TOKEN, $request->request->get(self::PARAM_TOKEN_NAME)))) {
            throw $this->createAccessDeniedException('Invalid CSRF Token');
        }

        $profile = $request->request->get(self::PARAM_PROFILE);
        if (!$this->profileManager->isValidProfile($profile)) {
            throw $this->createNotFoundException('Invalid profile given');
        }

        $this->profileManager->setProfile($request->getSession(), $profile);
        $csrfTokenManager->refreshToken(self::DATATABLE_TOKEN);

        return new Response();
    }

    #[Route(path: '/datatable/save', name: 'bookmark_save_datatable', methods: ['POST'])]
    public function datatableSave(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$request->request->has(self::PARAM_TOKEN_NAME) || !$request->request->has(self::PARAM_DATATABLE) || !$request->request->has(self::PARAM_PROFILE)) {
            throw $this->createNotFoundException('Missing data: csrf token, datatable name or profile');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::DATATABLE_TOKEN, $request->request->get(self::PARAM_TOKEN_NAME)))) {
            throw $this->createAccessDeniedException('Invalid CSRF Token');
        }

        $profile = $request->request->get(self::PARAM_PROFILE);
        if (!$this->profileManager->isValidProfile($profile)) {
            throw $this->createNotFoundException('Invalid profile given');
        }

        $datatableName = $request->request->get(self::PARAM_DATATABLE);
        $datatableName = $this->profileManager->getDatatableName($datatableName, $profile);

        if (empty($datatableName) || mb_strlen($datatableName) > 50) {
            throw new RuntimeException('Invalid datatable name');
        }

        $enabled = [];
        foreach ($request->request->all() as $name => $value) {
            if ($value !== 'on' || mb_strlen($name) > 30) {
                continue;
            }
            $enabled[$name] = true;
        }

        if (\count($enabled) > 50) {
            throw new RuntimeException(sprintf('Too many columns provided, expected maximum 50, received %s.', \count($enabled)));
        }

        $user = $this->getUser();

        $bookmark = $this->bookmarkRepository->findBookmark($user, Bookmark::COLUMN_VISIBILITY, $datatableName);
        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setType(Bookmark::COLUMN_VISIBILITY);
            $bookmark->setName($datatableName);
        }
        $bookmark->setContent($enabled);

        $this->bookmarkRepository->saveBookmark($bookmark);
        $this->profileManager->setProfile($request->getSession(), $profile);
        $csrfTokenManager->refreshToken(self::DATATABLE_TOKEN);

        return new Response();
    }

    #[Route(path: '/datatable/delete', name: 'bookmark_delete', methods: ['POST'])]
    public function datatableDelete(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$request->request->has(self::PARAM_TOKEN_NAME) || !$request->request->has(self::PARAM_DATATABLE) || !$request->request->has(self::PARAM_PROFILE)) {
            throw $this->createNotFoundException('Missing data: csrf token, datatable name or profile');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::DATATABLE_TOKEN, $request->request->get(self::PARAM_TOKEN_NAME)))) {
            throw $this->createAccessDeniedException('Invalid CSRF Token');
        }

        $profile = $request->request->get(self::PARAM_PROFILE);
        if (!$this->profileManager->isValidProfile($profile)) {
            throw $this->createNotFoundException('Invalid profile given');
        }

        $datatableName = $request->request->get(self::PARAM_DATATABLE);
        $datatableName = $this->profileManager->getDatatableName($datatableName, $profile);

        $bookmark = $this->bookmarkRepository->findBookmark($this->getUser(), Bookmark::COLUMN_VISIBILITY, $datatableName);
        if ($bookmark !== null) {
            $this->bookmarkRepository->deleteBookmark($bookmark);
        }

        $csrfTokenManager->refreshToken(self::DATATABLE_TOKEN);

        return new Response();
    }
}
