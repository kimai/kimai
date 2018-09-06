<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * The abstract base controller.
 */
abstract class AbstractController extends Controller
{
    public const FLASH_SUCCESS = 'success';
    public const FLASH_WARNING = 'warning';
    public const FLASH_ERROR = 'error';

    public const DOMAIN_FLASH = 'flashmessages';
    public const DOMAIN_ERROR = 'exceptions';

    public const ROLE_ADMIN = 'ROLE_ADMIN';

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
     * @param string $translationKey
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
     * @param string $translationKey
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
