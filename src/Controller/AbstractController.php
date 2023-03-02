<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Bookmark;
use App\Entity\User;
use App\Repository\BookmarkRepository;
use App\Repository\Query\BaseQuery;
use App\Timesheet\DateTimeFactory;
use App\Validator\ValidationFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractController extends BaseAbstractController implements ServiceSubscriberInterface
{
    protected function getUser(): User
    {
        $user = parent::getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException('Missing user');
        }

        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException('Expected Kimai user, received unknown type');
        }

        return $user;
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get('translator');
    }

    protected function createSearchForm(string $type = FormType::class, $data = null, array $options = []): FormInterface
    {
        return $this->createFormForGetRequest($type, $data, $options);
    }

    protected function createFormForGetRequest(string $type = FormType::class, $data = null, array $options = []): FormInterface
    {
        return $this->container
            ->get('form.factory')
            ->createNamed('', $type, $data, array_merge(['method' => 'GET'], $options));
    }

    protected function createFormWithName(string $name, string $type, mixed $data = null, array $options = []): FormInterface
    {
        return $this->container->get('form.factory')->createNamed($name, $type, $data, $options);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * This needs to be a 201 code and NOT 302 (as usual for redirects) because 302 cannot be handled on
     * javascript side, as the fetch() API will auto-redirect these responses without access to the header.
     */
    protected function redirectToRouteAfterCreate(string $route, array $parameters = []): RedirectResponse
    {
        $url = $this->generateUrl($route, $parameters);
        $response = new RedirectResponse($url, 201);
        $response->headers->set('x-modal-redirect', $url);

        return $response;
    }

    /**
     * Adds a "successful" flash message to the stack.
     */
    protected function flashSuccess(string $translationKey): void
    {
        $this->addFlashTranslated('success', $translationKey);
    }

    /**
     * Adds a "warning" flash message to the stack.
     */
    protected function flashWarning(string $translationKey): void
    {
        $this->addFlashTranslated('warning', $translationKey);
    }

    /**
     * Adds an "error" flash message to the stack.
     *
     * @param string $translationKey
     * @param array<string, string>|string $reason passing an array is deprecated
     * @return void
     * @throws \Exception
     */
    protected function flashError(string $translationKey, array|string $reason = ''): void
    {
        if (\is_array($reason)) {
            @trigger_error('Calling "flashError" with an array $reason is deprecated and will be removed soon. Refactor and pass a string instead.', E_USER_DEPRECATED);
            $reason = \array_key_exists('%reason%', $reason) ? $reason['%reason%'] : '';
        }

        $this->addFlashTranslated('error', $translationKey, ['%reason%' => $reason]);
    }

    /**
     * Adds an exception flash message for failed update/create actions.
     */
    protected function flashUpdateException(\Exception $exception): void
    {
        $this->flashException($exception, 'action.update.error');
    }

    /**
     * Adds an exception flash message for failed delete actions.
     */
    protected function flashDeleteException(\Exception $exception): void
    {
        $this->flashException($exception, 'action.delete.error');
    }

    /**
     * Adds an "error" flash message and logs the Exception.
     */
    protected function flashException(\Exception $exception, string $translationKey): void
    {
        $this->logException($exception);

        $this->addFlashTranslated('error', $translationKey, ['%reason%' => $exception->getMessage()]);
    }

    /**
     * Adds a fully translated (both $message and all keys in $parameter) flash message to the stack.
     *
     * @param string $type
     * @param string $message
     * @param array<string, string> $parameter
     * @return void
     * @throws \Exception
     */
    private function addFlashTranslated(string $type, string $message, array $parameter = []): void
    {
        if (!empty($parameter)) {
            foreach ($parameter as $key => $value) {
                $parameter[$key] = $this->getTranslator()->trans($value, [], 'flashmessages');
            }
            $message = $this->getTranslator()->trans(
                $message,
                $parameter,
                'flashmessages'
            );
        }

        $this->addFlash($type, $message);
    }

    /**
     * Handles exception flash messages for failed update/create actions.
     */
    protected function handleFormUpdateException(\Exception $exception, FormInterface $form): void
    {
        if (!($exception instanceof ValidationFailedException)) {
            $this->flashUpdateException($exception);

            return;
        }

        $msg = $this->getTranslator()->trans($exception->getMessage(), [], 'validators');
        if ($exception->getViolations()->count() > 0) {
            for ($i = 0; $i < $exception->getViolations()->count(); $i++) {
                $violation = $exception->getViolations()->get($i);
                $form->addError(new FormError($violation->getMessage()));
            }
        } else {
            $form->addError(new FormError($msg));
        }
    }

    protected function logException(\Exception $ex): void
    {
        $this->container->get('logger')->critical($ex->getMessage());
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'translator' => TranslatorInterface::class,
            'logger' => LoggerInterface::class,
            BookmarkRepository::class => BookmarkRepository::class,
        ]);
    }

    protected function getDateTimeFactory(?User $user = null): DateTimeFactory
    {
        if (null === $user) {
            $user = $this->getUser();
        }

        return DateTimeFactory::createByUser($user);
    }

    // ================================ SEARCH AND BOOKMARKS ================================

    private function getBookmark(): BookmarkRepository
    {
        return $this->container->get(BookmarkRepository::class);
    }

    private function getLastSearch(SessionInterface $session, BaseQuery $query): ?array
    {
        $name = 'search_' . $this->getSearchName($query);

        if (!$session->has($name)) {
            return null;
        }

        return $session->get($name);
    }

    private function removeLastSearch(SessionInterface $session, BaseQuery $query): void
    {
        $name = 'search_' . $this->getSearchName($query);

        if ($session->has($name)) {
            $session->remove($name);
        }
    }

    private function getSearchName(BaseQuery $query): string
    {
        return substr($query->getName(), 0, 50);
    }

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param array<string> $filterParams parameter names, which should not be saved (neither session, nor database)
     * @return bool
     * @throws \Exception
     */
    protected function handleSearch(FormInterface $form, Request $request, array $filterParams = []): bool
    {
        $data = $form->getData();
        if (!($data instanceof BaseQuery)) {
            throw new \InvalidArgumentException('handleSearchForm() requires an instanceof BaseQuery as form data');
        }

        $actions = ['resetSearchFilter', 'removeDefaultQuery', 'setDefaultQuery'];
        foreach ($actions as $action) {
            if ($request->query->has($action)) {
                if (!$this->isCsrfTokenValid('search', $request->query->get('_token'))) {
                    $this->flashError('action.csrf.error');

                    return false;
                }
            }
        }

        $request->query->remove('_token');

        if ($request->query->has('resetSearchFilter')) {
            $data->resetFilter();
            $this->removeLastSearch($request->getSession(), $data);

            return true;
        }

        $queryKey = null;
        if (!empty($formName = $form->getConfig()->getName()) && $request->request->has($formName)) {
            // allow using forms with block-prefix
            $queryKey = $formName;
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            $submitData = $request->request->all($queryKey);
        } else {
            $submitData = $request->query->all($queryKey);
        }
        $searchName = $this->getSearchName($data);

        /** @var BookmarkRepository $bookmarkRepo */
        $bookmarkRepo = $this->getBookmark();
        $bookmark = $bookmarkRepo->getSearchDefaultOptions($this->getUser(), $searchName);

        if ($bookmark !== null) {
            if ($request->query->has('removeDefaultQuery')) {
                $bookmarkRepo->deleteBookmark($bookmark);
                $bookmark = null;

                return true;
            } else {
                $data->setBookmark($bookmark);
            }
        }

        // apply persisted search data ONLY if search form was not submitted manually
        if (!$request->query->has('performSearch')) {
            $sessionSearch = $this->getLastSearch($request->getSession(), $data);
            if ($sessionSearch !== null) {
                $submitData = array_merge($sessionSearch, $submitData);
            } elseif ($bookmark !== null && !$request->query->has('setDefaultQuery')) {
                $bookContent = $bookmark->getContent();
                $isBookmarkSearch = true;
                foreach ($submitData as $key => $value) {
                    if (!\array_key_exists($key, $bookContent) || $value !== $bookContent[$key]) {
                        $isBookmarkSearch = false;
                        break;
                    }
                }
                if ($isBookmarkSearch) {
                    $data->flagAsBookmarkSearch();
                }

                $submitData = array_merge($bookContent, $submitData);
            }
        }

        // clean up parameters from unknown search values
        foreach ($submitData as $name => $values) {
            if (!$form->has($name)) {
                unset($submitData[$name]);
            }
        }

        $form->submit($submitData, false);

        if (!$form->isValid()) {
            $data->resetByFormError($form->getErrors(true));

            return false;
        }

        $params = [];
        foreach ($form->all() as $name => $child) {
            $params[$name] = $child->getViewData();
        }

        // these should NEVER be saved
        $filter = array_merge(['setDefaultQuery', 'removeDefaultQuery', 'performSearch'], $filterParams);
        foreach ($filter as $name) {
            if (isset($params[$name])) {
                unset($params[$name]);
            }
        }

        if ($request->query->has('performSearch')) {
            $request->getSession()->set('search_' . $searchName, $params);
        }

        // filter stuff, that does not belong in a bookmark
        $filter = ['page'];
        foreach ($filter as $name) {
            if (isset($params[$name])) {
                unset($params[$name]);
            }
        }

        if ($request->query->has('setDefaultQuery')) {
            $this->removeLastSearch($request->getSession(), $data);
            if ($bookmark === null) {
                $bookmark = new Bookmark();
                $bookmark->setType(Bookmark::SEARCH_DEFAULT);
                $bookmark->setUser($this->getUser());
                $bookmark->setName($searchName);
            }

            $bookmark->setContent($params);
            $bookmarkRepo->saveBookmark($bookmark);

            return true;
        }

        return false;
    }
}
