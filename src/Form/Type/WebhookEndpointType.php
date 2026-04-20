<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Renders a single webhook endpoint row: URL, secret, and a list of events
 * the endpoint is subscribed to.
 *
 * Events are per-action, not per-entity — receivers can subscribe to
 * `timesheet.created` without also receiving `timesheet.updated`. The
 * authoritative list of firing events comes from `#[AsWebhook]`
 * attributes on Kimai's domain event classes; this type ships a
 * hand-maintained mirror grouped by entity so the UI can render
 * `<optgroup>`-style grouped checkboxes. Adding a new `#[AsWebhook]`
 * event requires adding it here too — no reflection at form-build time
 * because attribute scanning all 20+ event classes per request is
 * overkill for a config page.
 *
 * The URL field is SSRF-hardened: by default, URLs that resolve to
 * private/loopback/link-local IPs are rejected so an admin can't aim
 * the dispatcher at the cloud-metadata endpoint (169.254.169.254) or
 * into RFC1918 ranges. Set `kimai.webhook.allow_private_network: true`
 * in `config/packages/local.yaml` to permit those for legitimate
 * intra-network receivers.
 */
final class WebhookEndpointType extends AbstractType
{
    /**
     * Every dispatched event grouped by entity, in display order. Keep in
     * sync with the `#[AsWebhook(name: ...)]` values on domain events.
     *
     * @var array<string, array<int, string>>
     */
    public const EVENT_CATALOG = [
        'timesheet' => ['timesheet.created', 'timesheet.updated', 'timesheet.stopped'],
        'customer' => ['customer.created', 'customer.updated', 'customer.deleted'],
        'project' => ['project.created', 'project.updated', 'project.deleted'],
        'activity' => ['activity.created', 'activity.updated', 'activity.deleted'],
        'invoice' => ['invoice.created', 'invoice.deleted'],
        'user' => ['user.created', 'user.updated', 'user.deleted'],
        'team' => ['team.created', 'team.updated', 'team.deleted'],
    ];

    /**
     * Entity keys in `EVENT_CATALOG` — also accepted as legacy shorthand
     * in the stored `events` array, meaning "all actions for this entity".
     * Kept so the data-preserving migration from the pr-5840 schema (which
     * had entity-level toggles only) doesn't need to re-expand, and so
     * admins who hand-edit the stored config via CLI don't have to
     * enumerate every action.
     */
    public const ENTITY_TYPES = ['timesheet', 'customer', 'project', 'activity', 'invoice', 'user', 'team'];

    /**
     * CIDR ranges blocked by default. Covers:
     *  - loopback                           (127.0.0.0/8, ::1)
     *  - link-local incl. cloud metadata    (169.254.0.0/16, fe80::/10)
     *  - RFC1918 private IPv4               (10/8, 172.16/12, 192.168/16)
     *  - IPv6 unique-local + doc ranges     (fc00::/7, 2001:db8::/32)
     *  - reserved / unspecified             (0.0.0.0/8, 224.0.0.0/4, 240.0.0.0/4, 255.255.255.255/32, ::/128)
     */
    private const PRIVATE_OR_RESERVED_RANGES = [
        '127.0.0.0/8',
        '169.254.0.0/16',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '0.0.0.0/8',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
        '::1/128',
        'fe80::/10',
        'fc00::/7',
        '2001:db8::/32',
        '::/128',
    ];

    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Choice label → value map, grouped by entity. Symfony's ChoiceType
        // renders nested-array choices as <optgroup> in selects and as
        // grouped checkbox sections in expanded mode.
        $eventChoices = [];
        foreach (self::EVENT_CATALOG as $entity => $events) {
            $groupLabel = 'webhook.entity.' . $entity;
            $eventChoices[$groupLabel] = [];
            foreach ($events as $event) {
                $eventChoices[$groupLabel]['webhook.event.' . $event] = $event;
            }
        }

        $builder
            ->add('url', UrlType::class, [
                'label' => 'webhook.endpoint.url',
                'required' => true,
                'translation_domain' => 'system-configuration',
                'constraints' => [
                    new NotBlank(message: 'Endpoint URL must not be empty.'),
                    new Url(message: 'Endpoint URL must be a valid http(s) URL.', protocols: ['http', 'https']),
                    new Callback([$this, 'validateUrlNotPrivate']),
                ],
                'attr' => ['placeholder' => 'https://example.com/webhook'],
            ])
            ->add('secret', PasswordType::class, [
                'label' => 'webhook.endpoint.secret',
                'required' => false,
                'always_empty' => false,
                'translation_domain' => 'system-configuration',
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('events', ChoiceType::class, [
                'label' => 'webhook.endpoint.events',
                'choices' => $eventChoices,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'translation_domain' => 'system-configuration',
                'choice_translation_domain' => 'system-configuration',
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
     * Reject URLs that resolve to private / loopback / link-local IPs.
     *
     * Invoked per-row. DNS is resolved once at validation time; this doesn't
     * protect against DNS rebinding between validate and dispatch — the
     * dispatcher re-checks at the HTTP client layer (see
     * WebhookService::buildUnsignedHttpClient()).
     *
     * Returns early when `kimai.webhook.allow_private_network` is set.
     *
     * @param mixed $value
     */
    public function validateUrlNotPrivate($value, ExecutionContextInterface $context): void
    {
        if (!\is_string($value) || $value === '') {
            return;
        }
        if ($this->systemConfiguration->find('webhook.allow_private_network')) {
            return;
        }

        $host = parse_url($value, \PHP_URL_HOST);
        if (!\is_string($host) || $host === '') {
            return;
        }

        // parse_url keeps surrounding brackets on IPv6 literals; strip before
        // validating so `[::1]` → `::1` passes FILTER_VALIDATE_IP.
        $bare = (\str_starts_with($host, '[') && \str_ends_with($host, ']'))
            ? substr($host, 1, -1)
            : $host;

        $ips = [];
        if (filter_var($bare, \FILTER_VALIDATE_IP)) {
            $ips[] = $bare;
        } else {
            $records = @dns_get_record($bare, \DNS_A | \DNS_AAAA);
            if (\is_array($records)) {
                foreach ($records as $r) {
                    if (isset($r['ip']) && \is_string($r['ip'])) {
                        $ips[] = $r['ip'];
                    }
                    if (isset($r['ipv6']) && \is_string($r['ipv6'])) {
                        $ips[] = $r['ipv6'];
                    }
                }
            }
            if ($ips === []) {
                // unresolvable — let it through; dispatch will fail and log. We
                // don't want to block webhook setup just because DNS is flaky.
                return;
            }
        }

        foreach ($ips as $ip) {
            if (IpUtils::checkIp($ip, self::PRIVATE_OR_RESERVED_RANGES)) {
                $context->buildViolation(
                    'Endpoint URL resolves to a private, loopback, or reserved IP ({{ ip }}). '
                    . 'Set `kimai.webhook.allow_private_network: true` in config/packages/local.yaml to permit internal webhook receivers.'
                )
                    ->setParameter('{{ ip }}', $ip)
                    ->addViolation();

                return;
            }
        }
    }
}
