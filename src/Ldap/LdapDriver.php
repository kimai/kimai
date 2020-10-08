<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Configuration\LdapConfiguration;
use Laminas\Ldap\Exception\LdapException;
use Laminas\Ldap\Ldap;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
    /**
     * @var LdapConfiguration
     */
    private $config;

    public function __construct(LdapConfiguration $config, LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Do not initialize in the constructor, as it is called in some situations from the Symfony DI container,
     * even if not actively used.
     *
     * So users without LDAP run into the exception which is thrown below if the package is not installed.
     *
     * To test the problematic behaviour:
     * - switch to "dev" env
     * - login as any user
     * - change the user ID in the database
     * - reload the page and see the exception
     *
     * @return Ldap
     * @throws \Exception
     */
    protected function getDriver()
    {
        if (null === $this->driver) {
            if (!class_exists('Laminas\Ldap\Ldap')) {
                throw new \Exception(
                    'Laminas\Ldap\Ldap is missing, install it with "composer require laminas/laminas-ldap" ' .
                    'or deactivate LDAP, see https://www.kimai.org/documentation/ldap.html'
                );
            }

            $this->driver = new Ldap($this->config->getConnectionParameters());
        }

        return $this->driver;
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
        $driver = $this->getDriver();

        $attributes = array_unique(array_merge($attributes, ['+', '*']));

        $this->logDebug('{action}({base_dn}, {filter}, {attributes})', [
            'action' => 'ldap_search',
            'base_dn' => $baseDn,
            'filter' => $filter,
            'attributes' => $attributes,
        ]);

        try {
            $driver->bind();
            $entries = $driver->searchEntries($filter, $baseDn, Ldap::SEARCH_SCOPE_SUB, $attributes);

            // searchEntries don't return 'count' key as specified by php native function ldap_get_entries()
            $entries['count'] = \count($entries);
        } catch (LdapException $exception) {
            $this->ldapExceptionHandler($exception);

            throw new LdapDriverException('An error occurred with the search operation.');
        }

        return $entries;
    }

    public function bind(UserInterface $user, string $password): bool
    {
        $driver = $this->getDriver();

        $bindDn = $user->getUsername();

        try {
            $this->logDebug('{action}({bindDn}, ****)', [
                'action' => 'ldap_bind',
                'bindDn' => $bindDn,
            ]);
            $bind = $driver->bind($bindDn, $password);

            return $bind instanceof Ldap;
        } catch (LdapException $exception) {
            $this->ldapExceptionHandler($exception, $password);
        }

        return false;
    }

    private function ldapExceptionHandler(LdapException $exception, string $password = null): void
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
