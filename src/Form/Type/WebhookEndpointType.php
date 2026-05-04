<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Webhook\Attribute\AsWebhook;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\HostnameValidator;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Renders a single webhook endpoint row:
 * - URL
 * - Secret
 * - List of events the endpoint is subscribed to
 */
final class WebhookEndpointType extends AbstractType
{
    /**
     * @var \ArrayObject<string, string>|null
     */
    private ?\ArrayObject $dnsCache = null;

    private const array PRIVATE_OR_RESERVED_RANGES = [
        '224.0.0.0/4', // IPv4 Multicast
        '255.255.255.255/32', // Local/Limited Broadcast
        '2001:db8::/32', // IPv6 Documentation Prefix
    ];

    /**
     * @param class-string[] $events
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly SystemConfiguration $systemConfiguration,
        private readonly array $events = []
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $groups = [];
        foreach ($this->events as $eventClass) {
            $a = (new \ReflectionClass($eventClass))->getAttributes(AsWebhook::class);
            foreach ($a as $attribute) {
                $args = $attribute->getArguments();
                $name = $args['name'];
                $grouped = explode('.', $name);
                $group = 'default';
                $event = $name;
                if (\count($grouped) === 2) {
                    $group = $grouped[0];
                    $event = $grouped[1];
                }
                if ($group === 'invoice') {
                    $group = 'invoices';
                }
                $groups[$group][$event] = [$name, $args['description']];
            }
        }

        $choices = [];
        foreach ($groups as $group => $events) {
            $tmp = [];
            foreach ($events as $event => $values) {
                $name = $this->translator->trans($event);
                if ($name === $event) {
                    $name = ucfirst($name);
                }
                $label = $name . ' (' . $values[1] . ')';
                $tmp[$label] = $values[0];
            }
            $choices[$this->translator->trans($group)] = $tmp;
        }

        $builder
            ->add('url', UrlType::class, [
                'label' => 'URL',
                'required' => true,
                'translation_domain' => 'system-configuration',
                'default_protocol' => 'https',
                'constraints' => [
                    new NotBlank(message: 'Endpoint URL must not be empty.'),
                    new Url(message: 'Endpoint URL must be a valid https URL.', protocols: ['http', 'https'], requireTld: false),
                    new Callback($this->validateUrlNotPrivate(...)),
                ],
                'attr' => ['placeholder' => 'https://example.com/webhook'],
            ])
            ->add('secret', SecretType::class, [
                'label' => 'secret',
                'required' => true,
                'translation_domain' => 'system-configuration',
                'constraints' => [
                    new NotBlank(message: 'You must supply a secret, which is used to sign the webhook message.'),
                ],
            ])
            ->add('events', ChoiceType::class, [
                'label' => 'events',
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'translation_domain' => 'system-configuration',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => static fn () => ['url' => '', 'secret' => '', 'events' => []],
            'translation_domain' => 'system-configuration',
            'label' => false,
        ]);
    }

    /**
     * This is a safety net against SSRF attacks:
     *
     * - validate if the given value is an IP or a hostname
     * - if hostname: fetch IP via DNS
     * - validate IP is a valid IPv4 or IPv6 address
     * - if `webhook.allow_private_network` is set, return
     * - otherwise validate that the IP is not in a private or reserved subnet
     *
     * @param mixed $value
     */
    public function validateUrlNotPrivate($value, ExecutionContextInterface $context): void
    {
        if (!\is_string($value) || $value === '') {
            return;
        }

        $host = parse_url($value, \PHP_URL_HOST);
        if (!\is_string($host) || $host === '') {
            return;
        }

        // parse_url keeps surrounding brackets on IPv6 literals; strip before
        // validating so `[::1]` → `::1` passes FILTER_VALIDATE_IP.
        $bare = (str_starts_with($host, '[') && str_ends_with($host, ']'))
            ? substr($host, 1, -1)
            : $host;

        $ip = $bare;

        if (!filter_var($bare, \FILTER_VALIDATE_IP)) {
            $constraint = new Hostname(requireTld: true);
            $validator = new HostnameValidator();
            $validator->initialize($context);
            $validator->validate($bare, $constraint);

            if ($this->dnsCache === null) {
                $this->dnsCache = new \ArrayObject();
            }

            $ip = $this->dnsResolve($this->dnsCache, $bare, \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6);

            // let's make sure that any resolved IP has a valid structure
            if (!filter_var($ip, \FILTER_VALIDATE_IP)) {
                $context
                    ->buildViolation('Failed resolving IP address from hostname.')
                    ->setParameter('{{ value }}', $bare)
                    ->addViolation();
            }
        }

        if ($this->systemConfiguration->isWebhookPrivateNetworkAllowed()) {
            return;
        }

        // validate subnets
        if (IpUtils::checkIp($ip, self::PRIVATE_OR_RESERVED_RANGES)) {
            $context
                ->buildViolation('URL resolves to a reserved IP.')
                ->setParameter('{{ value }}', $ip)
                ->addViolation();

            return;
        }

        $ipFlags = \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE;

        if (false !== filter_var($ip, \FILTER_VALIDATE_IP, $ipFlags) && !IpUtils::checkIp($ip, IpUtils::PRIVATE_SUBNETS)) {
            return;
        }

        $context
            ->buildViolation('URL resolves to a private or reserved IP.')
            ->setParameter('{{ value }}', $ip)
            ->addViolation();
    }

    /**
     * See Symfony\Component\HttpClient\NoPrivateNetworkHttpClient
     *
     * @param \ArrayObject<string, string> $dnsCache
     */
    private function dnsResolve(\ArrayObject $dnsCache, string $host, int $ipFlags): string
    {
        if ($ip = filter_var(trim($host, '[]'), \FILTER_VALIDATE_IP) ?: false) {
            return $ip;
        }

        if ($dnsCache->offsetExists($host)) {
            return $dnsCache[$host]; // @phpstan-ignore return.type
        }

        // fetch IPv4 addresses
        $ip = gethostbynamel($host);
        if ((\FILTER_FLAG_IPV4 & $ipFlags) && $ip) {
            return $dnsCache[$host] = $ip[0];
        }

        if (!(\FILTER_FLAG_IPV6 & $ipFlags)) {
            return $host;
        }

        // if we reach this part, we likely have an invalid domain name

        // dns_get_record() can take minutes, so if the
        // DNS query takes too long, break the script instead of waiting
        set_time_limit(10);

        if (\extension_loaded('sockets')) {
            if (!$info = socket_addrinfo_lookup($host, null, ['ai_socktype' => \SOCK_STREAM, 'ai_family' => \AF_INET6])) {
                return $host;
            }

            $ip = socket_addrinfo_explain($info[0])['ai_addr']['sin6_addr'];
        } elseif ($ip = dns_get_record($host, \DNS_AAAA)) {
            $ip = $ip[0]['ipv6'];
        } elseif ('localhost' === $host || 'localhost.' === $host) {
            $ip = '::1';
        } else {
            return $host;
        }

        return $dnsCache[$host] = $ip;
    }
}
