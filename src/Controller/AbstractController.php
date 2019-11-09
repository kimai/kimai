<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The abstract base controller.
 */
abstract class AbstractController extends BaseAbstractController implements ServiceSubscriberInterface
{
    public const FLASH_SUCCESS = 'success';
    public const FLASH_WARNING = 'warning';
    public const FLASH_ERROR = 'error';

    public const DOMAIN_FLASH = 'flashmessages';
    public const DOMAIN_ERROR = 'exceptions';

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
     * Adds a "successful" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashSuccess($translationKey, $parameter = [])
    {
        $this->addFlashTranslated(self::FLASH_SUCCESS, $translationKey, $parameter);
    }

    /**
     * Adds a "warning" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashWarning($translationKey, $parameter = [])
    {
        $this->addFlashTranslated(self::FLASH_WARNING, $translationKey, $parameter);
    }

    /**
     * Adds a "error" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashError($translationKey, $parameter = [])
    {
        $this->addFlashTranslated(self::FLASH_ERROR, $translationKey, $parameter);
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
                $parameter[$key] = $this->getTranslator()->trans($value, [], self::DOMAIN_FLASH);
            }
            $message = $this->getTranslator()->trans(
                $message,
                $parameter,
                self::DOMAIN_FLASH
            );
        }

        $this->addFlash($type, $message);
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'translator' => TranslatorInterface::class
        ]);
    }
}
