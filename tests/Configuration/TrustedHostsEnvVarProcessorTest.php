<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\TrustedHostsEnvVarProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(TrustedHostsEnvVarProcessor::class)]
class TrustedHostsEnvVarProcessorTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);
    }

    private function process(mixed $value): string
    {
        $sut = new TrustedHostsEnvVarProcessor();

        return $sut->getEnv('trusted_hosts', 'TRUSTED_HOSTS', fn (string $name) => $value);
    }

    public function testGetProvidedTypes(): void
    {
        self::assertEquals(['trusted_hosts' => 'string'], TrustedHostsEnvVarProcessor::getProvidedTypes());
    }

    #[DataProvider('getEnvData')]
    public function testGetEnv(mixed $value, string $expected): void
    {
        self::assertSame($expected, $this->process($value));
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function getEnvData(): array
    {
        return [
            // empty values
            [null, ''],
            ['', ''],
            ['  ', ''],
            // plain host names: anchored, escaped and subdomains allowed
            ['kimai.example.com', '^(?:.+\.)?(?:kimai\.example\.com)$'],
            [' kimai.example.com ', '^(?:.+\.)?(?:kimai\.example\.com)$'],
            ['localhost|127.0.0.1|kimai.example.com', '^(?:.+\.)?(?:localhost|127\.0\.0\.1|kimai\.example\.com)$'],
            ['localhost|[::1]', '^(?:.+\.)?(?:localhost|\[\:\:1\])$'],
            // anchored host names with unescaped dots: escaped, no subdomains
            ['^kimai.example.com$', '^(?:kimai\.example\.com)$'],
            ['^kimai.example.com\z', '^(?:kimai\.example\.com)$'],
            ['^kimai-example.com', '^(?:kimai\-example\.com)$'],
            ['\Akimai.example.com\z', '^(?:kimai\.example\.com)$'],
            ['\Akimai.example.com\Z', '^(?:kimai\.example\.com)$'],
            ['^localhost$|^kimai.example.com$', '^(?:localhost|kimai\.example\.com)$'],
            // only an end anchor: escaped, subdomains allowed
            ['kimai.example.com$', '^(?:.+\.)?(?:kimai\.example\.com)$'],
            ['kimai.example.com\z', '^(?:.+\.)?(?:kimai\.example\.com)$'],
            ['kimai.example.com\Z', '^(?:.+\.)?(?:kimai\.example\.com)$'],
            // anchors are evaluated per entry
            ['^localhost|kimai.example.com$', '^(?:localhost|(?:.+\.)?(?:kimai\.example\.com))$'],
            ['localhost|^kimai.example.com$', '^(?:kimai\.example\.com|(?:.+\.)?(?:localhost))$'],
            // regular expressions are not changed
            ['^kimai\.example\.com$', '^kimai\.example\.com$'],
            ['^kimai\.example\.com\z', '^kimai\.example\.com\z'],
            ['^(kimai\.example\.com|localhost)$', '^(kimai\.example\.com|localhost)$'],
            ['.*\.example\.com', '.*\.example\.com'],
            ['^(a|b)\.example\.com$', '^(a|b)\.example\.com$'],
            ['^localhost$|^kimai\.example\.com$', '^localhost$|^kimai\.example\.com$'],
            ['\akimai.example.com', '\akimai.example.com'],
            ['kimai.example.com\b', 'kimai.example.com\b'],
            ['localhost||kimai.example.com', 'localhost||kimai.example.com'],
        ];
    }

    #[DataProvider('getHostData')]
    public function testHostIsTrusted(string $value, string $host, bool $trusted): void
    {
        Request::setTrustedHosts([$this->process($value)]);

        $request = Request::create('http://localhost/');
        $request->headers->set('HOST', $host);

        if (!$trusted) {
            $this->expectException(SuspiciousOperationException::class);
        }

        self::assertEquals($host, $request->getHost());
    }

    /**
     * @return array<int, array<int, string|bool>>
     */
    public static function getHostData(): array
    {
        return [
            ['kimai.example.com', 'kimai.example.com', true],
            ['kimai.example.com', 'www.kimai.example.com', true],
            ['kimai.example.com', 'kimai.example.com.attacker.lab', false],
            ['kimai.example.com', 'kimai-example.com', false],
            ['kimai.example.com', 'attackerkimai.example.com', false],
            ['example.com', 'kimai.example.com', true],
            ['example.com', 'example.com.attacker.lab', false],
            ['localhost|127.0.0.1|kimai.example.com', 'localhost', true],
            ['localhost|127.0.0.1|kimai.example.com', '127.0.0.1', true],
            ['localhost|127.0.0.1|kimai.example.com', 'kimai.example.com', true],
            ['localhost|127.0.0.1|kimai.example.com', 'localhost.attacker.lab', false],
            ['localhost|127.0.0.1|kimai.example.com', 'attacker-localhost.lab', false],
            ['localhost|127.0.0.1|kimai.example.com', 'kimai.example.com.attacker.lab', false],
            ['localhost|[::1]', '[::1]', true],
            ['^kimai.example.com$', 'kimai.example.com', true],
            ['^kimai.example.com$', 'www.kimai.example.com', false],
            ['^kimai.example.com$', 'kimai-example.com', false],
            ['^kimai.example.com\z', 'kimai.example.com', true],
            ['^kimai.example.com\z', 'kimai.example.com.attacker.lab', false],
            // only a start anchor
            ['^kimai.example.com', 'kimai.example.com', true],
            ['^kimai.example.com', 'www.kimai.example.com', false],
            ['^kimai.example.com', 'kimai.example.com.attacker.lab', false],
            ['^kimai.example.com', 'kimai-example.com', false],
            // only an end anchor
            ['kimai.example.com$', 'kimai.example.com', true],
            ['kimai.example.com$', 'www.kimai.example.com', true],
            ['kimai.example.com$', 'attackerkimai.example.com', false],
            ['kimai.example.com$', 'kimai.example.com.attacker.lab', false],
            ['kimai.example.com$', 'kimai-example.com', false],
            ['\Akimai.example.com\Z', 'kimai.example.com', true],
            ['\Akimai.example.com\Z', 'kimai-example.com', false],
            // anchors per entry
            ['^localhost$|^kimai.example.com$', 'localhost', true],
            ['^localhost$|^kimai.example.com$', 'kimai.example.com', true],
            ['^localhost$|^kimai.example.com$', 'kimai-example.com', false],
            ['^localhost$|^kimai.example.com$', 'localhost.attacker.lab', false],
            ['^localhost|kimai.example.com$', 'www.kimai.example.com', true],
            ['^localhost|kimai.example.com$', 'www.localhost', false],
            ['^localhost|kimai.example.com$', 'localhost.attacker.lab', false],
            ['^kimai\.example\.com$', 'kimai.example.com', true],
            ['^kimai\.example\.com$', 'kimai-example.com', false],
            ['.*\.example\.com', 'kimai.example.com', true],
        ];
    }
}
