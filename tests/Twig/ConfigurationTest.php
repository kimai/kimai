<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Constants;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Twig\Configuration;
use App\Twig\SecurityPolicy\StrictPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityError;
use Twig\TwigFunction;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    private function createEnvironment(array $templates = ['template' => ''], bool $sandboxed = false): Environment
    {
        $environment = new Environment(new ArrayLoader($templates));

        if ($sandboxed) {
            $sandbox = new SandboxExtension(new StrictPolicy());
            $environment->addExtension($sandbox);
            $sandbox->enableSandbox();
        }

        return $environment;
    }

    private function createExtension(array $settings = []): Configuration
    {
        return new Configuration(SystemConfigurationFactory::createStub($settings));
    }

    private function getDefaultSettings(): array
    {
        return [
            'theme' => [
                'show_about' => true,
                'avatar_url' => true,
                'branding' => [
                    'logo' => 'logo.png',
                    'company' => 'Acme Inc.',
                ],
            ],
            'user' => [
                'login' => false,
                'password_reset' => true,
                'password_reset_token_ttl' => 3600,
            ],
            'ldap' => [
                'activate' => false,
            ],
            'saml' => [
                'activate' => false,
                'title' => 'SAML Login',
            ],
        ];
    }

    public function testGetFunctions(): void
    {
        $sut = $this->createExtension($this->getDefaultSettings());

        $functions = $sut->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('config', $functions[0]->getName());

        $environment = $this->createEnvironment(['template' => '{{ config("theme.branding.company") }}']);
        $environment->addExtension($sut);

        self::assertSame('Acme Inc.', $environment->render('template'));
    }

    public static function provideFixedConfigValues(): iterable
    {
        yield 'chart class' => ['chart-class', ''];
        yield 'chart background' => ['theme.chart.background_color', '#3c8dbc'];
        yield 'chart border' => ['theme.chart.border_color', '#3b8bba'];
        yield 'chart grid' => ['theme.chart.grid_color', 'rgba(0,0,0,.05)'];
        yield 'chart height' => ['theme.chart.height', '300'];
        yield 'calendar background' => ['theme.calendar.background_color', Constants::DEFAULT_COLOR];
    }

    #[DataProvider('provideFixedConfigValues')]
    public function testReturnsFixedConfigValues(string $name, string $expected): void
    {
        $sut = $this->createExtension();

        self::assertSame($expected, $sut->get($this->createEnvironment(), $name));
    }

    public static function provideSandboxAllowedTemplates(): iterable
    {
        yield 'avatar urls' => ['{{ config("themeAllowAvatarUrls") ? "1" : "0" }}', '1'];
        yield 'branding logo' => ['{{ config("theme.branding.logo") }}', 'logo.png'];
        yield 'branding company' => ['{{ config("theme.branding.company") }}', 'Acme Inc.'];
    }

    #[DataProvider('provideSandboxAllowedTemplates')]
    public function testSandboxAllowsWhitelistedConfigAccess(string $template, string $expected): void
    {
        $environment = $this->createEnvironment(['template' => $template], true);
        $environment->addExtension($this->createExtension($this->getDefaultSettings()));

        self::assertSame($expected, $environment->render('template'));
    }

    public static function provideSensitiveConfigKeys(): iterable
    {
        yield 'saml active' => ['saml.activate'];
        yield 'saml title' => ['saml.title'];
        yield 'ldap active' => ['ldap.activate'];
        yield 'ldap connection' => ['ldap.connection.host'];
    }

    #[DataProvider('provideSensitiveConfigKeys')]
    public function testRejectsSensitiveConfigKeys(string $name): void
    {
        $sut = $this->createExtension($this->getDefaultSettings());

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessage(\sprintf('Templates cannot access security configuration %s.', $name));

        $sut->get($this->createEnvironment([], true), $name);
    }

    public function testSandboxRejectsNonWhitelistedConfigAccess(): void
    {
        $environment = $this->createEnvironment(['template' => '{{ config("showAbout") ? "1" : "0" }}'], true);
        $environment->addExtension($this->createExtension($this->getDefaultSettings()));

        $this->expectException(SecurityError::class);
        $this->expectExceptionMessage('Sandboxed template tried to access configuration key: showAbout');

        $environment->render('template');
    }

    public function testUsesIsAccessorOutsideSandbox(): void
    {
        $sut = $this->createExtension($this->getDefaultSettings());

        self::assertTrue($sut->get($this->createEnvironment(), 'showAbout'));
        self::assertTrue($sut->get($this->createEnvironment(), 'loginFormActive'));
    }

    public function testUsesGetAccessorOutsideSandbox(): void
    {
        $sut = $this->createExtension($this->getDefaultSettings());

        self::assertSame(3600, $sut->get($this->createEnvironment(), 'passwordResetTokenLifetime'));
    }

    public function testFallsBackToFindOutsideSandbox(): void
    {
        $sut = $this->createExtension($this->getDefaultSettings());
        $environment = $this->createEnvironment();

        self::assertTrue($sut->get($environment, 'theme.show_about'));
        self::assertNull($sut->get($environment, 'does.not.exist'));
    }

    public function testDeprecatedKimaiConfigAccessAlwaysReturnsNull(): void
    {
        $sut = $this->createExtension($this->getDefaultSettings());

        $previousHandler = set_error_handler(static function (int $type, string $message): true {
            self::assertSame(E_USER_DEPRECATED, $type);
            self::assertSame('Accessing "kimai_config" is deprecated and always return null, use config() instead', $message);

            return true;
        });

        try {
            self::assertNull($sut->__call('legacyAccess', []));
        } finally {
            restore_error_handler();
        }
    }
}
