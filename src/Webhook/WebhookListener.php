<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

use App\Webhook\Attribute\AsWebhook;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Contracts\EventDispatcher\Event;

final class WebhookListener implements EventSubscriberInterface
{
    private ?ExpressionLanguage $expressionLanguage = null;

    public function __construct(private readonly WebhookService $webhookService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // See WebhookEventAliasCompilerPass: events are registered during container compilation
        return [];
    }

    public function triggerWebhook(Event $event): void
    {
        if (!$this->webhookService->isConfigured()) {
            return;
        }

        $attributes = (new \ReflectionClass($event))->getAttributes(AsWebhook::class);
        // the attribute is not-repeatable and there should be only one trigger per event
        if (\count($attributes) !== 1) {
            return;
        }

        $args = $attributes[0]->getArguments();

        if ($this->expressionLanguage === null) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

        $parsed = $this->expressionLanguage->parse($args['payload'], ['object']);
        $payload = $parsed->getNodes()->evaluate([], ['object' => $event]);

        $this->webhookService->trigger($args['name'], $payload);
    }
}
