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
use App\Twig\SecurityPolicy\DefaultPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

#[CoversClass(DefaultPolicy::class)]
class DefaultPolicyTest extends TestCase
{
    public function testCheckSecurity(): void
    {
        $sut = new DefaultPolicy();
        $sut->checkSecurity([], [], []);
        $this->expectNotToPerformAssertions();
    }

    public function testCheckPropertyAllowed(): void
    {
        $sut = new DefaultPolicy();
        $sut->checkPropertyAllowed(new \stdClass(), 'foo');
        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('getCheckMethodAllowedData')]
    public function testCheckMethodAllowed($obj, string $method, ?string $expectedExceptionMessage = null, ?string $expectedClass = null): void
    {
        $sut = new DefaultPolicy();

        if ($expectedExceptionMessage !== null) {
            $this->expectException(SecurityNotAllowedPropertyError::class);
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
            // Request
            [new Request(), 'get', null],
            [new Request(), 'isXmlHttpRequest', 'Tried to call setter() of app variable'],
            [new Request(), 'hasSession', 'Tried to call setter() of app variable'],
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
        ];
    }
}
