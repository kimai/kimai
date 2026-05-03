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
use App\Event\CustomerCreatePostEvent;
use App\Event\InvoiceCreatedEvent;
use App\Event\TimesheetCreatePostEvent;
use App\Form\Type\WebhookEndpointType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(WebhookEndpointType::class)]
class WebhookEndpointTypeTest extends TestCase
{
    private function createType(bool $allowPrivate = false): WebhookEndpointType
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);
        $settings = ['webhook.allow_private_network' => $allowPrivate];

        return new WebhookEndpointType(
            $this->createStub(TranslatorInterface::class),
            new SystemConfiguration($configLoader, $settings)
        );
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
        self::assertCount(0, $violations);
    }

    public function testValidatorIsBypassedWhenAllowPrivateNetworkIsTrue(): void
    {
        $violations = $this->validate('http://127.0.0.1/hook', allowPrivate: true);
        self::assertCount(0, $violations, 'Private IPs should pass when allow_private_network is true');
    }

    public function testValidatorIgnoresEmptyInput(): void
    {
        $violations = $this->validate('');
        self::assertCount(0, $violations);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function nonStringValues(): array
    {
        return [
            'integer' => [42],
            'null' => [null],
            'array' => [['https://example.com']],
            'true' => [true],
        ];
    }

    #[DataProvider('nonStringValues')]
    public function testValidatorIgnoresNonStringValues(mixed $value): void
    {
        $type = $this->createType();
        $violations = Validation::createValidator()->validate(
            $value,
            new Callback([$type, 'validateUrlNotPrivate']),
        );

        self::assertCount(0, $violations);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function urlsWithoutHost(): array
    {
        return [
            'plain string' => ['not-a-url'],
            'mailto scheme' => ['mailto:foo@bar.com'],
            'path only' => ['/just/a/path'],
        ];
    }

    #[DataProvider('urlsWithoutHost')]
    public function testValidatorIgnoresUrlsWithoutHost(string $url): void
    {
        $violations = $this->validate($url);
        self::assertCount(0, $violations);
    }

    public function testValidatorEmitsReservedIpMessageForMulticast(): void
    {
        $violations = $this->validate('http://224.0.0.5/hook');
        self::assertCount(1, $violations);
        self::assertSame('URL resolves to a reserved IP.', $violations->get(0)->getMessage());
    }

    public function testValidatorEmitsPrivateIpMessageForRfc1918(): void
    {
        $violations = $this->validate('http://10.0.0.5/hook');
        self::assertCount(1, $violations);
        self::assertSame('URL resolves to a private or reserved IP.', $violations->get(0)->getMessage());
    }

    public function testValidatorAllowsPublicIpv4Literal(): void
    {
        $violations = $this->validate('https://8.8.8.8/hook');
        self::assertCount(0, $violations);
    }

    public function testValidatorAllowsPublicIpv6Literal(): void
    {
        $violations = $this->validate('https://[2001:4860:4860::8888]/hook');
        self::assertCount(0, $violations);
    }

    public function testValidatorBypassAlsoClearsReservedIpViolations(): void
    {
        $violations = $this->validate('http://224.0.0.5/hook', allowPrivate: true);
        self::assertCount(0, $violations, 'allow_private_network=true must short-circuit before the reserved-IP check too');
    }

    public function testConfigureOptionsHasExpectedDefaults(): void
    {
        $resolver = new OptionsResolver();
        $this->createType()->configureOptions($resolver);
        $defaults = $resolver->resolve();

        self::assertNull($defaults['data_class']);
        self::assertSame('system-configuration', $defaults['translation_domain']);
        self::assertFalse($defaults['label']);

        self::assertIsCallable($defaults['empty_data']);
        self::assertSame(
            ['url' => '', 'secret' => '', 'events' => []],
            ($defaults['empty_data'])(),
        );
    }

    /**
     * @param class-string[] $events
     */
    private function createForm(array $events): \Symfony\Component\Form\FormInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);

        $type = new WebhookEndpointType(
            $translator,
            new SystemConfiguration($configLoader, ['webhook.allow_private_network' => true]),
            $events,
        );

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($type)
            ->getFormFactory();

        return $factory->create(WebhookEndpointType::class);
    }

    public function testFormHasUrlSecretAndEventsFields(): void
    {
        $form = $this->createForm([]);

        self::assertTrue($form->has('url'));
        self::assertTrue($form->has('secret'));
        self::assertTrue($form->has('events'));
    }

    public function testEventChoicesAreGroupedByDotPrefix(): void
    {
        $form = $this->createForm([CustomerCreatePostEvent::class, TimesheetCreatePostEvent::class]);

        $choices = $form->get('events')->getConfig()->getOption('choices');

        self::assertIsArray($choices);
        self::assertArrayHasKey('customer', $choices);
        self::assertArrayHasKey('timesheet', $choices);
        self::assertContains('customer.created', $choices['customer']);
        self::assertContains('timesheet.created', $choices['timesheet']);
    }

    public function testInvoiceGroupIsRenamedToInvoices(): void
    {
        $form = $this->createForm([InvoiceCreatedEvent::class]);

        $choices = $form->get('events')->getConfig()->getOption('choices');

        self::assertIsArray($choices);
        self::assertArrayHasKey('invoices', $choices);
        self::assertArrayNotHasKey('invoice', $choices);
        self::assertContains('invoice.created', $choices['invoices']);
    }

    public function testEventChoiceLabelsIncludeDescription(): void
    {
        $form = $this->createForm([CustomerCreatePostEvent::class]);

        $choices = $form->get('events')->getConfig()->getOption('choices');

        self::assertSame(
            ['Created (Triggered after a customer was created)' => 'customer.created'],
            $choices['customer'],
        );
    }
}
