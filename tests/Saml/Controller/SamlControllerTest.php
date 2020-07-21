<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Controller;

use App\Saml\Controller\SamlController;
use App\Tests\Mocks\Saml\SamlAuthFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Xml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @group integration
 */
class SamlControllerTest extends TestCase
{
    protected function getAuth()
    {
        return (new SamlAuthFactory($this))->create();
    }

    public function testAssertionConsumerServiceAction()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must configure the check path in your firewall.');

        $oauth = $this->getAuth();
        $sut = new SamlController($oauth);
        $sut->assertionConsumerServiceAction();
    }

    public function testLogoutAction()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must configure the logout path in your firewall.');

        $oauth = $this->getAuth();
        $sut = new SamlController($oauth);
        $sut->logoutAction();
    }

    public function testMetadataAction()
    {
        $expected = <<<EOD
<?xml version="1.0"?>
    <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" validUntil="2020-07-23T10:26:50Z" cacheDuration="PT604800S" entityID="https://127.0.0.1:8010/auth/saml/metadata">
        <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="false" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
            <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://127.0.0.1:8010/auth/saml/logout" />
            <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
            <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://127.0.0.1:8010/auth/saml/acs" index="1" />
        </md:SPSSODescriptor>
        <md:Organization>
           <md:OrganizationName xml:lang="en">Kimai</md:OrganizationName>
           <md:OrganizationDisplayName xml:lang="en">Kimai</md:OrganizationDisplayName>
           <md:OrganizationURL xml:lang="en">https://www.kimai.org</md:OrganizationURL>
        </md:Organization>
        <md:ContactPerson contactType="technical">
            <md:GivenName>Kimai Admin</md:GivenName>
            <md:EmailAddress>kimai-tech@example.com</md:EmailAddress>
        </md:ContactPerson>
        <md:ContactPerson contactType="support">
            <md:GivenName>Kimai Support</md:GivenName>
            <md:EmailAddress>kimai-support@example.com</md:EmailAddress>
        </md:ContactPerson>
    </md:EntityDescriptor>
EOD;

        $oauth = $this->getAuth();
        $sut = new SamlController($oauth);
        $result = $sut->metadataAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertEquals('xml', $result->headers->get('Content-Type'));

        $expected = Xml::load($expected);
        $actual = Xml::load($result->getContent());

        // the "validUntil" attribute in the outer node changes per request
        self::assertEquals($expected->firstChild->firstChild, $actual->firstChild->firstChild);
    }

    public function testLoginActionThrowsErrorOnSecurityErrorAttribute()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('My test error');

        $request = new Request();
        $request->setSession($this->createMock(SessionInterface::class));
        $request->attributes->set(Security::AUTHENTICATION_ERROR, new \Exception('My test error'));

        $oauth = $this->getAuth();
        $sut = new SamlController($oauth);
        $sut->loginAction($request);
    }
}
