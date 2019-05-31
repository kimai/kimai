<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Zend\Ldap\Exception\LdapException;
use Zend\Ldap\Ldap;

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
     * @param Ldap            $driver Initialized Zend::Ldap Object
     * @param LoggerInterface $logger optional logger for write debug messages
     */
    public function __construct(Ldap $driver, LoggerInterface $logger = null)
    {
        $this->driver = $driver;
        $this->logger = $logger;
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
            $entries = $this->driver->searchEntries($filter, $baseDn, Ldap::SEARCH_SCOPE_SUB, $attributes);
            // searchEntries don't return 'count' key as specified by php native
            // function ldap_get_entries()
            $entries['count'] = count($entries);
        } catch (LdapException $exception) {
            $this->zendExceptionHandler($exception);

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
            $this->zendExceptionHandler($exception, $password);
        }

        return false;
    }

    /**
     * Treat a Zend Ldap Exception.
     */
    protected function zendExceptionHandler(LdapException $exception, string $password = null): void
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
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }
}
