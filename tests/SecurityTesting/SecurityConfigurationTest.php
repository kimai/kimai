<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Security configuration regression tests for OWASP WSTG v4.2 category
 * "4.2 Configuration and Deployment Management Testing" (WSTG-CONF).
 *
 * Guards the security relevant Symfony configuration against accidental
 * weakening (e.g. disabled rate limiting or removed cookie flags).
 */
#[Group('security')]
class SecurityConfigurationTest extends TestCase
{
    private const CONFIG_DIR = __DIR__ . '/../../config/packages/';
    private const ROUTES_DIR = __DIR__ . '/../../config/routes/';

    /**
     * WSTG-ATHN-03 / WSTG-CONF-01:
     * Login throttling (brute-force protection) must stay enabled and strict.
     */
    public function testLoginThrottlingIsEnabled(): void
    {
        $throttling = self::getSubArray(self::loadConfig('security.yaml'), 'security', 'firewalls', 'secured_area', 'login_throttling');

        self::assertArrayHasKey('max_attempts', $throttling, 'Login throttling misses max_attempts');
        self::assertIsNumeric($throttling['max_attempts']);
        self::assertLessThanOrEqual(5, $throttling['max_attempts'], 'Login throttling is too permissive');
    }

    /**
     * WSTG-CONF-01 / CSRF prevention:
     * The login form must keep CSRF protection enabled.
     */
    public function testLoginFormHasCsrfProtection(): void
    {
        $formLogin = self::getSubArray(self::loadConfig('security.yaml'), 'security', 'firewalls', 'secured_area', 'form_login');

        self::assertTrue($formLogin['enable_csrf'] ?? false, 'CSRF protection for the login form is disabled');
    }

    /**
     * WSTG-CONF-01 / WSTG-ATHZ-01:
     * The API firewall must require authentication for every API route.
     */
    public function testApiAccessIsRestrictedToAuthenticatedUsers(): void
    {
        $rules = self::getSubArray(self::loadConfig('security.yaml'), 'security', 'access_control');

        $apiRule = null;
        foreach ($rules as $rule) {
            self::assertIsArray($rule);
            if (isset($rule['path']) && \is_string($rule['path']) && str_starts_with($rule['path'], '^/api')) {
                $apiRule = $rule;
            }
        }

        self::assertNotNull($apiRule, 'Missing access_control rule for ^/api');
        $role = $apiRule['role'] ?? $apiRule['roles'] ?? null;
        self::assertSame('IS_AUTHENTICATED_REMEMBERED', $role, 'API is not restricted to authenticated users');
    }

    /**
     * WSTG-SESS-02 / WSTG-CONF-01:
     * Session cookie hardening flags must not be weakened.
     */
    public function testSessionCookieFlagsAreHardened(): void
    {
        $session = self::getSubArray(self::loadConfig('framework.yaml'), 'framework', 'session');

        self::assertTrue($session['cookie_httponly'] ?? false, 'Session cookie misses HttpOnly');
        self::assertContains($session['cookie_samesite'] ?? null, ['lax', 'strict'], 'Session cookie misses SameSite');
        self::assertSame('auto', $session['cookie_secure'] ?? null, 'Session cookie should use secure=auto');
    }

    /**
     * WSTG-ATHN-03 / WSTG-CONF-01:
     * Password reset requests must stay rate limited to prevent abuse.
     */
    public function testPasswordResetIsRateLimited(): void
    {
        $limiters = self::getSubArray(self::loadConfig('rate_limiter.yaml'), 'framework', 'rate_limiter');

        self::assertArrayHasKey('reset_password', $limiters, 'Password reset rate limiter was removed');
    }

    /**
     * WSTG-CONF-05:
     * Debug mode must be disabled in the environment that actually ships.
     *
     * This deliberately does NOT read $_ENV['APP_DEBUG']: phpunit.xml.dist sets
     * that variable with force="true", so the test harness overwrites it before
     * any test runs and the assertion could only ever observe its own fixture.
     * The deployment compose file is the artefact a regression would land in.
     */
    public function testDeployedEnvironmentDisablesDebug(): void
    {
        $environment = self::getDeployedAppEnvironment();

        self::assertArrayHasKey('APP_ENV', $environment, 'Deployment no longer pins APP_ENV');
        self::assertSame('prod', $environment['APP_ENV'], 'Deployment does not run in the prod environment');

        self::assertArrayHasKey('APP_DEBUG', $environment, 'Deployment no longer pins APP_DEBUG');
        // compose may quote the value or leave it bare, so YAML yields '0' or 0 -
        // assert it is scalar before normalising rather than casting blindly
        $debug = $environment['APP_DEBUG'];
        self::assertIsScalar($debug, 'APP_DEBUG is not a scalar value');
        self::assertSame(
            '0',
            (string) $debug,
            'APP_DEBUG is enabled in the deployed environment, leaking stack traces and internals'
        );
    }

    /**
     * WSTG-CONF-05:
     * The profiler and its routes must never be reachable outside dev/test.
     *
     * The HTTP-level check in ErrorHandlingSecurityTest cannot prove this on its
     * own - under APP_ENV=test those routes are simply not registered, so it
     * would pass even if the profiler were enabled in production. These are the
     * two conditions that actually keep it off a deployed instance.
     */
    public function testProfilerIsConfinedToDevelopmentEnvironments(): void
    {
        // 1. Profiler routes live in config/routes/dev/, which Kernel.php only
        //    imports when the active environment directory matches.
        self::assertFileExists(
            self::ROUTES_DIR . 'dev/web_profiler.yaml',
            'Profiler routes moved - re-check that they are still dev-only'
        );
        foreach (['prod', 'test'] as $environment) {
            self::assertFileDoesNotExist(
                self::ROUTES_DIR . $environment . '/web_profiler.yaml',
                \sprintf('Profiler routes are registered for the "%s" environment', $environment)
            );
        }

        // 2. web_profiler.yaml must gate every key behind a when@ block. A
        //    top-level key would apply to prod as well.
        $profilerConfig = Yaml::parseFile(self::CONFIG_DIR . 'web_profiler.yaml');
        self::assertIsArray($profilerConfig);
        foreach (array_keys($profilerConfig) as $key) {
            self::assertMatchesRegularExpression(
                '/^when@(dev|test)$/',
                (string) $key,
                \sprintf('web_profiler.yaml key "%s" is not restricted to dev or test', $key)
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function getDeployedAppEnvironment(): array
    {
        $path = __DIR__ . '/../../devsecops/deploy/docker-compose.yml';
        self::assertFileExists($path, 'Deployment compose file is missing');

        /** @var array<string, mixed> $compose */
        $compose = Yaml::parseFile($path);
        self::assertIsArray($compose);

        $environment = self::getSubArray($compose, 'services', 'app', 'environment');
        self::assertNotEmpty($environment, 'Deployed app service declares no environment');

        return $environment;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadConfig(string $file): array
    {
        $path = self::CONFIG_DIR . $file;
        self::assertFileExists($path);

        /** @var array<string, mixed> $data */
        $data = Yaml::parseFile($path);
        self::assertIsArray($data);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function getSubArray(array $data, string ...$path): array
    {
        $current = $data;
        foreach ($path as $key) {
            self::assertIsArray($current);
            self::assertArrayHasKey($key, $current, \sprintf('Missing configuration key: %s', implode(' > ', $path)));
            /** @var array<string, mixed> $current */
            $current = $current[$key];
        }

        return $current;
    }
}
