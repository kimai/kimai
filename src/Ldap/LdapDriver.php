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

/**
 * @internal
 */
class LdapDriver
{
    private ?Ldap $driver = null;

    public function __construct(
        private readonly LdapConfiguration $config,
        private readonly LoggerInterface $logger
    )
    {
    }

    protected function getDriver(): Ldap
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
     * @param array<int, string> $attributes
     * @return array{'count': int, array<string, string|array<int, string>>}
     * @throws LdapDriverException
     */
    public function search(string $baseDn, string $filter, array $attributes = []): array
    {
        $driver = $this->getDriver();

        $attributes = array_unique(array_merge($attributes, ['+', '*']));

        $this->logger->debug('{action}({base_dn}, {filter}, {attributes})', [
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
            $this->logger->error(\sprintf('Failed to search LDAP: %s', $exception->getMessage()), ['exception' => $exception]);

            throw new LdapDriverException('An error occurred with the search operation.');
        }

        return $entries;
    }

    public function bind(string $bindDn, string $password): bool
    {
        $driver = $this->getDriver();

        try {
            $this->logger->debug('{action}({bindDn}, ****)', [
                'action' => 'ldap_bind',
                'bindDn' => $bindDn,
            ]);
            $bind = $driver->bind($bindDn, $password);

            return $bind instanceof Ldap;
        } catch (LdapException $exception) {
            $this->logger->error(\sprintf('Failed binding to LDAP at %s: %s', $bindDn, $exception->getMessage()), ['exception' => new SanitizingException($exception, $password)]);
        }

        return false;
    }
}
