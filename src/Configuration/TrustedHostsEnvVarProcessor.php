<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Symfony matches TRUSTED_HOSTS as an unanchored regular expression, so the value "kimai.example.com"
 * also trusts "kimai.example.com.attacker.tld" and "kimai-example.com".
 *
 * Values that only consist of host names (joined by "|", each optionally wrapped in anchors like "^...$")
 * are converted into an anchored pattern with escaped dots. Host names without start anchor also accept
 * their subdomains. Every other value is treated as a deliberately written regular expression and returned unchanged.
 *
 * Usage: %env(trusted_hosts:TRUSTED_HOSTS)%
 */
final class TrustedHostsEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * One entry: optional start anchor (^ or \A), a host name or IPv6 address, optional end anchor ($, \z or \Z)
     */
    private const ENTRY = '/^(\^|(?-i:\\\\A))?([a-z0-9_.-]+|\[[0-9a-f:.]+\])(?:\$|\\\\z)?$/iD';

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $value = $getEnv($name);

        return $this->normalize(\is_scalar($value) ? (string) $value : '');
    }

    public static function getProvidedTypes(): array
    {
        return ['trusted_hosts' => 'string'];
    }

    private function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        $exact = [];
        $subdomains = [];

        foreach (array_map('trim', explode('|', $value)) as $entry) {
            if (preg_match(self::ENTRY, $entry, $matches) !== 1) {
                return $value;
            }

            // without start anchor the entry was matched as substring before, keep accepting subdomains of that host
            if ($matches[1] === '') {
                $subdomains[] = preg_quote($matches[2]);
            } else {
                $exact[] = preg_quote($matches[2]);
            }
        }

        $hosts = $exact;
        if ($subdomains !== []) {
            $hosts[] = '(?:.+\.)?(?:' . implode('|', $subdomains) . ')';
        }

        // a trailing dot is the fully qualified form of the same host name (e.g. "kimai.example.com.")
        if (\count($hosts) === 1 && $exact === []) {
            return '^' . $hosts[0] . '\.?$';
        }

        return '^(?:' . implode('|', $hosts) . ')\.?$';
    }
}
