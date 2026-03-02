<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

use App\Configuration\SystemConfiguration;
use App\Event\ActivityCreatePostEvent;
use App\Event\ActivityDeleteEvent;
use App\Event\ActivityUpdatePostEvent;
use App\Event\CustomerCreatePostEvent;
use App\Event\CustomerDeleteEvent;
use App\Event\CustomerUpdatePostEvent;
use App\Event\InvoiceCreatedEvent;
use App\Event\InvoiceDeleteEvent;
use App\Event\ProjectCreatePostEvent;
use App\Event\ProjectDeleteEvent;
use App\Event\ProjectUpdatePostEvent;
use App\Event\TeamCreatePostEvent;
use App\Event\TeamDeleteEvent;
use App\Event\TeamUpdatePostEvent;
use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetStopPostEvent;
use App\Event\TimesheetUpdatePostEvent;
use App\Event\UserCreatePostEvent;
use App\Event\UserDeletePostEvent;
use App\Event\UserUpdatePostEvent;
use App\Webhook\Attribute\AsWebhook;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Contracts\EventDispatcher\Event;

final class WebhookListener implements EventSubscriberInterface
{
    private const KNOWN_ENTITY_TYPES = [
        'timesheet',
        'customer',
        'project',
        'activity',
        'invoice',
        'user',
        'team',
    ];

    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        private readonly WebhookService $webhookService,
        private readonly SystemConfiguration $systemConfiguration,
        private readonly LoggerInterface $logger
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ActivityCreatePostEvent::class => ['triggerWebhook', 1000],
            ActivityDeleteEvent::class => ['triggerWebhook', 1000],
            ActivityUpdatePostEvent::class => ['triggerWebhook', 1000],

            CustomerCreatePostEvent::class => ['triggerWebhook', 1000],
            CustomerDeleteEvent::class => ['triggerWebhook', 1000],
            CustomerUpdatePostEvent::class => ['triggerWebhook', 1000],

            InvoiceCreatedEvent::class => ['triggerWebhook', 1000],
            InvoiceDeleteEvent::class => ['triggerWebhook', 1000],

            ProjectCreatePostEvent::class => ['triggerWebhook', 1000],
            ProjectDeleteEvent::class => ['triggerWebhook', 1000],
            ProjectUpdatePostEvent::class => ['triggerWebhook', 1000],

            TimesheetCreatePostEvent::class => ['triggerWebhook', 1000],
            TimesheetStopPostEvent::class => ['triggerWebhook', 1000],
            TimesheetUpdatePostEvent::class => ['triggerWebhook', 1000],

            UserCreatePostEvent::class => ['triggerWebhook', 1000],
            UserDeletePostEvent::class => ['triggerWebhook', 1000],
            UserUpdatePostEvent::class => ['triggerWebhook', 1000],

            TeamCreatePostEvent::class => ['triggerWebhook', 1000],
            TeamDeleteEvent::class => ['triggerWebhook', 1000],
            TeamUpdatePostEvent::class => ['triggerWebhook', 1000],
        ];
    }

    public function triggerWebhook(Event $event): void
    {
        $attribute = $this->findAttribute($event);
        if ($attribute === null) {
            return;
        }

        if (!$this->isEventEnabled($attribute->name)) {
            return;
        }

        if (!$this->webhookService->isConfigured()) {
            return;
        }

        $parsed = $this->expressionLanguage->parse($attribute->payload, ['object']);
        $payload = $parsed->getNodes()->evaluate([], ['object' => $event]);

        $this->webhookService->trigger($attribute->name, $payload);
    }

    private function isEventEnabled(string $eventName): bool
    {
        $parts = explode('.', $eventName);
        if (\count($parts) < 2) {
            return false;
        }

        $entityType = $parts[0];

        if (!\in_array($entityType, self::KNOWN_ENTITY_TYPES, true)) {
            return false;
        }

        $configKey = 'webhook.events.' . $entityType;

        return (bool) $this->systemConfiguration->find($configKey);
    }

    private function findAttribute(Event $event): ?AsWebhook
    {
        try {
            $reflectionClass = new \ReflectionClass($event);
            $attributes = $reflectionClass->getAttributes(AsWebhook::class);
            if (\count($attributes) === 1) {
                $attribute = $attributes[0];
                $args = $attribute->getArguments();

                return new AsWebhook($args['name'], $args['description'], $args['payload']);
            }
        } catch (\Exception $ex) {
            $this->logger->warning('Failed to read webhook attribute from event {event}: {message}', [
                'event' => $event::class,
                'message' => $ex->getMessage(),
            ]);
        }

        return null;
    }
}
