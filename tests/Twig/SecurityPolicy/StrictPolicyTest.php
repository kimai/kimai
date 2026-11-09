<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\SecurityPolicy;

use App\Entity\User;
use App\Pdf\PdfContext;
use App\Twig\SecurityPolicy\StrictPolicy;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\String\UnicodeString;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityPolicyInterface;

#[CoversClass(StrictPolicy::class)]
class StrictPolicyTest extends TestCase
{
    private function createPolicy(): SecurityPolicyInterface
    {
        return new StrictPolicy();
    }

    public function testCheckSecurity(): void
    {
        $sut = $this->createPolicy();
        $sut->checkSecurity([], [], []);
        $this->expectNotToPerformAssertions();
    }

    public function testCheckPropertyAllowed(): void
    {
        $sut = $this->createPolicy();
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $sut->checkPropertyAllowed($obj, 'foo');
        $sut->checkPropertyAllowed($obj, 'unknown');
        // protected property, Twig falls back to getLocale()
        $sut->checkPropertyAllowed(new Request(), 'locale');
        // non-existing property, Twig falls back to the method check
        $sut->checkPropertyAllowed(new User(), 'username');
        // array access, e.g. collection['foo']
        $sut->checkPropertyAllowed(new ArrayCollection(['foo' => 'bar']), 'foo');
        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('getCheckPropertyNotAllowedData')]
    public function testCheckPropertyNotAllowed(object $obj, string $property): void
    {
        $sut = $this->createPolicy();

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $this->expectExceptionMessage(\sprintf('Tried to access property "%s" of "%s"', $property, $obj::class));

        $sut->checkPropertyAllowed($obj, $property);
    }

    public static function getCheckPropertyNotAllowedData(): array
    {
        return [
            [new Request(), 'headers'],
            [new Request(), 'cookies'],
            [new Request(), 'query'],
            [new Request(), 'request'],
            [new Request(), 'attributes'],
            [new Request(), 'files'],
            [new Request(), 'server'],
        ];
    }

    #[DataProvider('getRenderTemplateData')]
    public function testRenderTemplateInSandbox(string $template, ?string $expected): void
    {
        $request = Request::create('/en/invoice/', 'POST', ['_token' => 'csrf-token'], ['KIMAI_SESSION' => 'secret-session-id'], [], [
            'HTTP_COOKIE' => 'KIMAI_SESSION=secret-session-id',
            'PHP_AUTH_USER' => 'basic-user',
            'PHP_AUTH_PW' => 'basic-password',
        ]);
        $request->setLocale('de');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $user = new User();
        $user->setUserIdentifier('test-foo');
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new RememberMeToken($user, 'main', 'test'));
        $app = new AppVariable();
        $app->setRequestStack($requestStack);
        $app->setTokenStorage($tokenStorage);

        $twig = new Environment(new ArrayLoader(['test' => $template]));
        $twig->addGlobal('app', $app);
        $twig->addExtension(new SandboxExtension($this->createPolicy(), true));

        if ($expected === null) {
            $this->expectException(SecurityError::class);
        }

        $result = $twig->render('test');

        self::assertEquals($expected, $result);
    }

    public static function getRenderTemplateData(): array
    {
        return [
            ['{{ app.request.locale }}', 'de'],
            ['{{ app.request.host }}', 'localhost'],
            ['{{ app.request.schemeAndHttpHost }}', 'http://localhost'],
            ["{{ app.request.headers.get('Cookie') }}", null],
            ["{{ app.request.cookies.get('KIMAI_SESSION') }}", null],
            ["{{ app.request.request.get('_token') }}", null],
            ["{{ app.request.server.get('HTTP_COOKIE') }}", null],
            ["{{ app.request.get('_token') }}", null],
            ['{{ app.request.content }}', null],
            ["{{ app.request.payload.get('_token') }}", null],
            ['{{ app.request.user }}', null],
            ['{{ app.request.password }}', null],
            ['{{ app.request.userInfo }}', null],
            ['{{ app.request.session.id }}', null],
            ['{{ app.request.server.get("APP_SECRET") }}', null],
            ['{{ app.request.server.get("DATABASE_URL") }}', null],
            ['{{ app.request.server.get("APP_ENV") }}', null],
            ['{{ app.request.server.get("APP_DEBUG") }}', null],
            ['{{ app.session.get("_security_secured_area") }}', null],
            ['{{ app.user.username }}', 'test-foo'],
            ['{{ app.user.password }}', null],
        ];
    }

    #[DataProvider('getCheckMethodAllowedData')]
    public function testCheckMethodAllowed(object $obj, string $method, ?string $expectedExceptionMessage = null): void
    {
        $sut = $this->createPolicy();

        if ($expectedExceptionMessage !== null) {
            $this->expectException(SecurityNotAllowedMethodError::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $sut->checkMethodAllowed($obj, $method);

        if ($expectedExceptionMessage === null) {
            $this->expectNotToPerformAssertions();
        }
    }

    public static function getCheckMethodAllowedData(): array
    {
        return [
            [new ServerBag(), 'get', 'Tried to access server environment'],
            [self::createStub(SessionInterface::class), 'getId', 'Tried to access session'],
            [new \stdClass(), 'foo', 'Tried to access non-read method'],
            [new \stdClass(), 'setFoo', 'Tried to access non-read method'],
            [new \stdClass(), 'getFoo'],
            [new \stdClass(), 'hasFoo'],
            [new \stdClass(), 'isFoo'],
            [new UnicodeString(), '__toString'],
            // request data
            [new HeaderBag(), 'get', 'Tried to access request data'],
            [new HeaderBag(), 'all', 'Tried to access request data'],
            [new InputBag(), 'get', 'Tried to access request data'],
            [new ParameterBag(), 'get', 'Tried to access request data'],
            [new FileBag(), 'get', 'Tried to access request data'],
            // Request
            [new Request(), 'getLocale'],
            [new Request(), 'getHost'],
            [new Request(), 'getSchemeAndHttpHost'],
            [new Request(), 'get', 'Tried to access forbidden request method'],
            [new Request(), 'getContent', 'Tried to access forbidden request method'],
            [new Request(), 'getPayload', 'Tried to access forbidden request method'],
            [new Request(), 'getUser', 'Tried to access forbidden request method'],
            [new Request(), 'getPassword', 'Tried to access forbidden request method'],
            [new Request(), 'getUserInfo', 'Tried to access forbidden request method'],
            [new Request(), 'getSession', 'Tried to access forbidden request method'],
            [new Request(), 'isXmlHttpRequest', 'Tried to access forbidden request method'],
            [new Request(), 'hasSession', 'Tried to access forbidden request method'],
            // PdfContext
            [new PdfContext(), 'setOption'],
            [new PdfContext(), 'getOption', 'Tried to access forbidden method on PdfContext'],
            // AppVariable
            [new AppVariable(), 'getRequest'],
            [new AppVariable(), 'getUser'],
            [new AppVariable(), 'getLocale'],
            [new AppVariable(), 'getCharset', 'Tried to access forbidden app variable method'],
            // User
            [new User(), 'getUsername'],
            [new User(), 'getPassword', 'Tried to access user secrets'],
            [new User(), 'getTotpSecret', 'Tried to access user secrets'],
            [new User(), 'getPlainPassword', 'Tried to access user secrets'],
            [new User(), 'getConfirmationToken', 'Tried to access user secrets'],
            [new User(), 'getTotpAuthenticationConfiguration', 'Tried to access user secrets'],
            [new User(), 'getPlainApiToken', 'Tried to access user secrets'],
            [new User(), 'getApiToken', 'Tried to access user secrets'],
        ];
    }
}
