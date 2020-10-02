<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\LanguageFormattings;
use App\Entity\User;
use App\Timesheet\DateTimeFactory;
use App\Utils\LocaleFormats;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Translation\DataCollectorTranslator;
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

    /**
     * @return DataCollectorTranslator
     */
    private function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * @return LoggerInterface $logger
     */
    private function getLogger()
    {
        return $this->container->get('logger');
    }

    /**
     * Adds a "successful" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashSuccess($translationKey, $parameter = [])
    {
        $this->addFlashTranslated('success', $translationKey, $parameter);
    }

    /**
     * Adds a "warning" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashWarning($translationKey, $parameter = [])
    {
        $this->addFlashTranslated('warning', $translationKey, $parameter);
    }

    /**
     * Adds a "error" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashError($translationKey, $parameter = [])
    {
        $this->addFlashTranslated('error', $translationKey, $parameter);
    }

    /**
     * Adds an exception flash message for failed update/create actions.
     *
     * @param \Exception $exception
     */
    protected function flashUpdateException(\Exception $exception)
    {
        $this->flashException($exception, 'action.update.error');
    }

    /**
     * Adds an exception flash message for failed delete actions.
     *
     * @param \Exception $exception
     */
    protected function flashDeleteException(\Exception $exception)
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
    protected function flashException(\Exception $exception, string $translationKey, array $parameter = [])
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
    protected function addFlashTranslated(string $type, string $message, array $parameter = [])
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

    protected function logException(\Exception $ex)
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

        return new DateTimeFactory(new \DateTimeZone($user->getTimezone()));
    }

    protected function getLocaleFormats(string $locale): LocaleFormats
    {
        return new LocaleFormats($this->container->get(LanguageFormattings::class), $locale);
    }
}
