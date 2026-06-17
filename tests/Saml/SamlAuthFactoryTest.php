<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Configuration\SamlConfigurationInterface;
use App\Saml\SamlAuthFactory;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(SamlAuthFactory::class)]
class SamlAuthFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        Utils::setProxyVars(false);
    }

    protected function tearDown(): void
    {
        Utils::setProxyVars(false);
    }

    public function testCreateReturnsAuthWithConfiguredConnectionWithoutMainRequest(): void
    {
        $connection = $this->createConnection();
        $sut = new SamlAuthFactory(new RequestStack(), $this->createConfiguration($connection));

        $auth = $sut->create();

        self::assertInstanceOf(Auth::class, $auth);
        self::assertSame($connection['idp']['entityId'], $auth->getSettings()->getIdPData()['entityId']);
        self::assertSame($connection['sp']['entityId'], $auth->getSettings()->getSPData()['entityId']);
        self::assertFalse(Utils::getProxyVars());
    }

    public function testCreateDoesNotEnableProxyVarsForUntrustedMainRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push($this->createRequest(false));

        $sut = new SamlAuthFactory($requestStack, $this->createConfiguration($this->createConnection()));
        $sut->create();

        self::assertFalse(Utils::getProxyVars());
    }

    public function testCreateEnablesProxyVarsForTrustedProxyMainRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push($this->createRequest(true));

        $sut = new SamlAuthFactory($requestStack, $this->createConfiguration($this->createConnection()));
        $sut->create();

        self::assertTrue(Utils::getProxyVars());
    }

    private function createConfiguration(array $connection): SamlConfigurationInterface
    {
        $configuration = $this->createMock(SamlConfigurationInterface::class);
        $configuration->method('getConnection')->willReturn($connection);

        return $configuration;
    }

    private function createRequest(bool $fromTrustedProxy): Request
    {
        /** @var Request&MockObject $request */
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->method('isFromTrustedProxy')->willReturn($fromTrustedProxy);

        return $request;
    }

    private function createConnection(): array
    {
        return [
            'idp' => [
                'entityId' => 'https://accounts.example.com/saml2',
                'singleSignOnService' => [
                    'url' => 'https://accounts.example.com/saml2/sso',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => 'asdf',
            ],
            'sp' => [
                'entityId' => 'https://kimai.example.com/auth/saml/metadata',
                'assertionConsumerService' => [
                    'url' => 'https://kimai.example.com/auth/saml/acs',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'singleLogoutService' => [
                    'url' => 'https://kimai.example.com/auth/saml/logout',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'privateKey' => '',
            ],
            'strict' => true,
            'debug' => true,
            'security' => [
                'nameIdEncrypted' => false,
                'authnRequestsSigned' => false,
                'logoutRequestSigned' => false,
                'logoutResponseSigned' => false,
                'wantMessagesSigned' => false,
                'wantAssertionsSigned' => false,
                'wantNameIdEncrypted' => false,
                'requestedAuthnContext' => true,
                'signMetadata' => false,
                'wantXMLValidation' => true,
                'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
                'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
            ],
        ];
    }
}
