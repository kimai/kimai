<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Saml;

use App\Configuration\SamlConfiguration;
use App\Saml\SamlAuthFactory;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\AbstractMockFactory;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SamlAuthFactoryFactory extends AbstractMockFactory
{
    public function create(?array $connection = null, bool $fromTrustedProxy = false): SamlAuthFactory
    {
        if (null === $connection) {
            $connection = [
                'idp' => [
                    'entityId' => 'https://accounts.google.com/o/saml2?idpid=',
                    'singleSignOnService' => [
                        'url' => 'https://accounts.google.com/o/saml2/idp?idpid=',
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    ],
                    'x509cert' => 'asdf',
                ],
                'sp' => [
                    'entityId' => 'https://127.0.0.1:8010/auth/saml/metadata',
                    'assertionConsumerService' => [
                        'url' => 'https://127.0.0.1:8010/auth/saml/acs',
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    ],
                    'singleLogoutService' => [
                        'url' => 'https://127.0.0.1:8010/auth/saml/logout',
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    ],
                    'privateKey' => ''
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
                'contactPerson' => [
                    'technical' => [
                        'givenName' => 'Kimai Admin',
                        'emailAddress' => 'kimai-tech@example.com',
                    ],
                    'support' => [
                        'givenName' => 'Kimai Support',
                        'emailAddress' => 'kimai-support@example.com',
                    ]
                ],
                'organization' => [
                    'en' => [
                        'name' => 'Kimai',
                        'displayname' => 'Kimai',
                        'url' => 'https://www.kimai.org',
                    ]
                ]
            ];
        }

        $mock = $this->getMockBuilder(Request::class)->getMock();
        $mock->method('isFromTrustedProxy')->willReturn($fromTrustedProxy);

        /** @var Request&MockObject $request */
        $request = $mock;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $configuration = SystemConfigurationFactory::create(new TestConfigLoader([]), [
            'saml' => ['connection' => $connection]
        ]);

        return new SamlAuthFactory($requestStack, new SamlConfiguration($configuration));
    }
}
