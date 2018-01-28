<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * The abstract base controller.
 */
abstract class AbstractController extends Controller
{
    const FLASH_SUCCESS = 'success';
    const FLASH_WARNING = 'warning';
    const FLASH_ERROR = 'error';

    const DOMAIN_FLASH = 'flashmessages';
    const DOMAIN_ERROR = 'exceptions';

    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @return object|\Symfony\Component\Translation\DataCollectorTranslator|\Symfony\Component\Translation\IdentityTranslator
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * A translated helper for denyAccessUnlessGranted()
     *
     * @param $attributes
     * @param null $subject
     * @param string $translation
     * @param array $parameter
     * @throws AccessDeniedException
     */
    protected function denyUnlessGranted($attributes, $subject = null, $translation = 'access.denied', $parameter = [])
    {
        $error = $this->getTranslator()->trans($translation, $parameter, self::DOMAIN_ERROR);
        // TODO try & catch and add to audit log?
        $this->denyAccessUnlessGranted($attributes, $subject, $error);
    }

    /**
     * Adds a "successful" flash message to the stack.
     *
     * @param string $translationKey
     * @param array $parameter
     */
    protected function flashSuccess($translationKey, $parameter = [])
    {
        if (!empty($parameter)) {
            $translationKey = $this->getTranslator()->trans(
                $translationKey,
                $parameter,
                self::DOMAIN_FLASH
            );
        }

        $this->addFlash(self::FLASH_SUCCESS, $translationKey);
    }

    /**
     * Adds a "warning" flash message to the stack.
     *
     * @param $translationKey
     * @param array $parameter
     */
    protected function flashWarning($translationKey, $parameter = [])
    {
        if (!empty($parameter)) {
            $translationKey = $this->getTranslator()->trans(
                $translationKey,
                $parameter,
                self::DOMAIN_FLASH
            );
        }

        $this->addFlash(self::FLASH_WARNING, $translationKey);
    }

    /**
     * Adds a "error" flash message to the stack.
     *
     * @param $translationKey
     * @param array $parameter
     */
    protected function flashError($translationKey, $parameter = [])
    {
        if (!empty($parameter)) {
            $translationKey = $this->getTranslator()->trans(
                $translationKey,
                $parameter,
                self::DOMAIN_FLASH
            );
        }

        $this->addFlash(self::FLASH_ERROR, $translationKey);
    }
}
