<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Configuration\LdapConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Zend\Ldap\Exception\LdapException;
use Zend\Ldap\Ldap;

/**
 * Inspired by https://github.com/Maks3w/FR3DLdapBundle @ MIT License
 */
class LdapDriver
{
    /**
     * @var Ldap
     */
    private $driver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LdapConfiguration $config, LoggerInterface $logger = null)
    {
        if ($config->isActivated()) {
            if (!class_exists('Zend\Ldap\Ldap')) {
                throw new \Exception('Zend\Ldap\Ldap is missing, install it with "composer require zendframework/zend-ldap"');
            }
            $this->setLdapConnection(new Ldap($config->getConnectionParameters()));
        }
        $this->logger = $logger;
    }

    /**
     * @param Ldap $ldap
     */
    public function setLdapConnection(Ldap $ldap)
    {
        $this->driver = $ldap;
    }

    /**
     * @param string $baseDn
     * @param string $filter
     * @param array $attributes
     * @return array
     * @throws LdapDriverException
     */
    public function search(string $baseDn, string $filter, array $attributes = []): array
    {
        $attributes = array_unique(array_merge($attributes, ['+', '*']));

        $this->logDebug('{action}({base_dn}, {filter}, {attributes})', [
            'action' => 'ldap_search',
            'base_dn' => $baseDn,
            'filter' => $filter,
            'attributes' => $attributes,
        ]);

        try {
            $this->driver->bind();
            $entries = $this->driver->searchEntries($filter, $baseDn, Ldap::SEARCH_SCOPE_SUB, $attributes);

            // searchEntries don't return 'count' key as specified by php native function ldap_get_entries()
            $entries['count'] = count($entries);
        } catch (LdapException $exception) {
            $this->ldapExceptionHandler($exception);

            throw new LdapDriverException('An error occurred with the search operation.');
        }

        return $entries;
    }

    public function bind(UserInterface $user, string $password): bool
    {
        $bindDn = $user->getUsername();

        try {
            $this->logDebug('{action}({bindDn}, ****)', [
                'action' => 'ldap_bind',
                'bindDn' => $bindDn,
            ]);
            $bind = $this->driver->bind($bindDn, $password);

            return $bind instanceof Ldap;
        } catch (LdapException $exception) {
            $this->ldapExceptionHandler($exception, $password);
        }

        return false;
    }

    protected function ldapExceptionHandler(LdapException $exception, string $password = null): void
    {
        $sanitizedException = null !== $password ? new SanitizingException($exception, $password) : $exception;

        switch ($exception->getCode()) {
            // Error level codes
            case LdapException::LDAP_SERVER_DOWN:
                if ($this->logger) {
                    $this->logger->error('{exception}', ['exception' => $sanitizedException]);
                }
                break;

            // Other level codes
            default:
                $this->logDebug('{exception}', ['exception' => $sanitizedException]);
                break;
        }
    }

    /**
     * Log debug messages if the logger is set.
     *
     * @param string $message
     * @param array $context
     */
    private function logDebug(string $message, array $context = []): void
    {
        if (null === $this->logger) {
            return;
        }
        $this->logger->debug($message, $context);
    }
}
