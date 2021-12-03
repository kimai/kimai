<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\LanguageFormattings;
use App\Entity\Bookmark;
use App\Entity\User;
use App\Repository\BookmarkRepository;
use App\Repository\Query\BaseQuery;
use App\Timesheet\DateTimeFactory;
use App\Utils\LocaleFormats;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The abstract base controller.
 * @method null|User getUser()
 */
abstract class AbstractController extends BaseAbstractController implements ServiceSubscriberInterface
{
    /**
     * @deprecated since 1.6, will be removed with 2.0
     */
    public const ROLE_ADMIN = User::ROLE_ADMIN;

    protected function getTranslator(): TranslatorInterface
    {
        return $this->container->get('translator');
    }

    private function getLogger(): LoggerInterface
    {
        return $this->container->get('logger');
    }

    /**
     * Adds a "successful" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashSuccess(string $translationKey, array $parameter = []): void
    {
        $this->addFlashTranslated('success', $translationKey, $parameter);
    }

    /**
     * Adds a "warning" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashWarning(string $translationKey, array $parameter = []): void
    {
        $this->addFlashTranslated('warning', $translationKey, $parameter);
    }

    /**
     * Adds a "error" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashError(string $translationKey, array $parameter = []): void
    {
        $this->addFlashTranslated('error', $translationKey, $parameter);
    }

    /**
     * Adds an exception flash message for failed update/create actions.
     *
     * @param \Exception $exception
     */
    protected function flashUpdateException(\Exception $exception): void
    {
        $this->flashException($exception, 'action.update.error');
    }

    /**
     * Adds an exception flash message for failed delete actions.
     *
     * @param \Exception $exception
     */
    protected function flashDeleteException(\Exception $exception): void
    {
        $this->flashException($exception, 'action.delete.error');
    }

    /**
     * Adds a "error" flash message and logs the Exception.
     *
     * @param \Exception $exception
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashException(\Exception $exception, string $translationKey, array $parameter = []): void
    {
        $this->logException($exception);

        if (!\array_key_exists('%reason%', $parameter)) {
            $parameter['%reason%'] = $exception->getMessage();
        }

        $this->addFlashTranslated('error', $translationKey, $parameter);
    }

    /**
     * Adds a fully translated (both $message and all keys in $parameter) flash message to the stack.
     *
     * @param string $type
     * @param string $message
     * @param array $parameter
     */
    protected function addFlashTranslated(string $type, string $message, array $parameter = []): void
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

    protected function logException(\Exception $ex): void
    {
        $this->getLogger()->critical($ex->getMessage());
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'translator' => TranslatorInterface::class,
            'logger' => LoggerInterface::class,
            LanguageFormattings::class => LanguageFormattings::class,
        ]);
    }

    protected function getDateTimeFactory(?User $user = null): DateTimeFactory
    {
        if (null === $user) {
            $user = $this->getUser();
        }

        return DateTimeFactory::createByUser($user);
    }

    protected function getLocaleFormats(string $locale): LocaleFormats
    {
        return new LocaleFormats($this->container->get(LanguageFormattings::class), $locale);
    }

    private function getLastSearch(BaseQuery $query): ?array
    {
        $name = 'search_' . $this->getSearchName($query);

        if (!$this->get('session')->has($name)) {
            return null;
        }

        return $this->get('session')->get($name);
    }

    private function removeLastSearch(BaseQuery $query): void
    {
        $name = 'search_' . $this->getSearchName($query);

        if ($this->get('session')->has($name)) {
            $this->get('session')->remove($name);
        }
    }

    private function getSearchName(BaseQuery $query): string
    {
        return substr($query->getName(), 0, 50);
    }

    /**
     * @param Request $request
     * @internal
     */
    protected function ignorePersistedSearch(Request $request): void
    {
        $request->query->set('performSearch', true);
    }

    protected function handleSearch(FormInterface $form, Request $request): bool
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
            $this->removeLastSearch($data);

            return true;
        }

        $submitData = $request->query->all();
        // allow to use forms with block-prefix
        if (!empty($formName = $form->getConfig()->getName()) && $request->request->has($formName)) {
            $submitData = $request->request->get($formName);
        }

        $searchName = $this->getSearchName($data);

        /** @var BookmarkRepository $bookmarkRepo */
        $bookmarkRepo = $this->getDoctrine()->getRepository(Bookmark::class);
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
            $sessionSearch = $this->getLastSearch($data);
            if ($sessionSearch !== null) {
                $submitData = array_merge($sessionSearch, $submitData);
            } elseif ($bookmark !== null && !$request->query->has('setDefaultQuery')) {
                $submitData = array_merge($bookmark->getContent(), $submitData);
                $data->flagAsBookmarkSearch();
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
        $filter = ['setDefaultQuery', 'removeDefaultQuery', 'performSearch'];
        foreach ($filter as $name) {
            if (isset($params[$name])) {
                unset($params[$name]);
            }
        }

        if ($request->query->has('performSearch')) {
            $this->get('session')->set('search_' . $searchName, $params);
        }

        // filter stuff, that does not belong in a bookmark
        $filter = ['page'];
        foreach ($filter as $name) {
            if (isset($params[$name])) {
                unset($params[$name]);
            }
        }

        if ($request->query->has('setDefaultQuery')) {
            $this->removeLastSearch($data);
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
