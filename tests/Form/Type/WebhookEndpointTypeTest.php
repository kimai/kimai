<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\SystemConfiguration;
use App\Form\Type\WebhookEndpointType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

#[CoversClass(WebhookEndpointType::class)]
class WebhookEndpointTypeTest extends TestCase
{
    private function createType(bool $allowPrivate = false): WebhookEndpointType
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);
        $settings = ['webhook.allow_private_network' => $allowPrivate];

        return new WebhookEndpointType(new SystemConfiguration($configLoader, $settings));
    }

    private function validate(string $url, bool $allowPrivate = false): ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();
        $type = $this->createType($allowPrivate);

        return $validator->validate(
            $url,
            new Callback([$type, 'validateUrlNotPrivate']),
        );
    }

    /**
     * Only IP-literal URLs are asserted here: hostname resolution requires working
     * DNS and is explicitly documented as "let it through; runtime wrapper catches
     * DNS rebinding attempts" in the validator's docblock.
     *
     * @return array<string, array{0: string}>
     */
    public static function blockedIpLiteralUrls(): array
    {
        return [
            'aws imds' => ['http://169.254.169.254/latest/meta-data/'],
            'loopback v4' => ['http://127.0.0.1:8080/hook'],
            'rfc1918 10' => ['http://10.0.0.5/hook'],
            'rfc1918 172' => ['http://172.16.1.1/hook'],
            'rfc1918 192' => ['http://192.168.1.1/hook'],
            'reserved 0.0.0.0' => ['http://0.0.0.0/hook'],
            'ipv6 loopback' => ['http://[::1]/hook'],
            'ipv6 link local' => ['http://[fe80::1]/hook'],
            'ipv6 unique-local' => ['http://[fc00::1]/hook'],
        ];
    }

    #[DataProvider('blockedIpLiteralUrls')]
    public function testValidatorRejectsPrivateOrReservedIps(string $url): void
    {
        $violations = $this->validate($url);
        self::assertGreaterThan(
            0,
            $violations->count(),
            "Expected URL {$url} to be rejected as private/reserved/link-local"
        );
    }

    public function testValidatorAllowsPublicUrls(): void
    {
        $violations = $this->validate('https://example.com/hook');
        self::assertSame(0, $violations->count());
    }

    public function testValidatorIsBypassedWhenAllowPrivateNetworkIsTrue(): void
    {
        $violations = $this->validate('http://127.0.0.1/hook', allowPrivate: true);
        self::assertSame(0, $violations->count(), 'Private IPs should pass when allow_private_network is true');
    }

    public function testValidatorIgnoresEmptyInput(): void
    {
        $violations = $this->validate('');
        self::assertSame(0, $violations->count());
    }
}
