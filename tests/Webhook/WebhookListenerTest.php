<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Webhook;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\SystemConfiguration;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\ActivityCreatePostEvent;
use App\Event\CustomerCreatePostEvent;
use App\Event\ProjectCreatePostEvent;
use App\Event\TeamCreatePostEvent;
use App\Event\TimesheetCreatePostEvent;
use App\Event\UserCreatePostEvent;
use App\Serializer\SerializerInterface;
use App\Webhook\WebhookListener;
use App\Webhook\WebhookService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Factory\MockUuidFactory;
use Symfony\Contracts\EventDispatcher\Event;

#[CoversClass(WebhookListener::class)]
class WebhookListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WebhookListener::getSubscribedEvents();

        self::assertEmpty($events);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createListener(array $settings, ?MessageBusInterface $bus = null, ?SerializerInterface $serializer = null): WebhookListener
    {
        $configLoader = $this->createMock(ConfigLoaderInterface::class);
        $configLoader->method('getConfigurations')->willReturn([]);
        $systemConfig = new SystemConfiguration($configLoader, $settings);

        if ($serializer === null) {
            $serializer = $this->createMock(SerializerInterface::class);
            $serializer->method('toArray')->willReturn(['id' => 1]);
        }

        if ($bus === null) {
            $bus = $this->createMock(MessageBusInterface::class);
            $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));
        }

        $webhookService = new WebhookService($systemConfig, $serializer, $bus, new MockUuidFactory(['1234567890']), new NullLogger());

        return new WebhookListener($webhookService);
    }

    /**
     * @param list<array{url: string, secret: string, events: list<string>}> $endpoints
     * @return array<string, mixed>
     */
    private function settings(array $endpoints): array
    {
        return [
            'webhook.endpoints' => json_encode($endpoints, \JSON_THROW_ON_ERROR),
            'webhook.max_endpoints' => 10,
            'webhook.allow_private_network' => true,
        ];
    }

    /**
     * @return array<string, array<Event>>
     */
    public static function getEventsByEntityType(): array
    {
        return [
            'timesheet' => [new TimesheetCreatePostEvent(new Timesheet())],
            'customer' => [new CustomerCreatePostEvent(new Customer('Test'))],
            'project' => [new ProjectCreatePostEvent(new Project())],
            'activity' => [new ActivityCreatePostEvent(new Activity())],
            'user' => [new UserCreatePostEvent(new User())],
            'team' => [new TeamCreatePostEvent(new Team('Test'))],
        ];
    }

    #[DataProvider('getEventsByEntityType')]
    public function testEventNotFiredWhenEntityNotSubscribed(Event $event): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::never())->method('toArray');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $listener = $this->createListener($this->settings([
            ['url' => 'https://example.com/webhook', 'secret' => 'secret', 'events' => ['__other__']],
        ]), $bus, $serializer);

        $listener->triggerWebhook($event);
    }

    public function testNoWebhookFiredWhenNotConfigured(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::never())->method('toArray');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $listener = $this->createListener($this->settings([]), $bus, $serializer);

        $event = new TimesheetCreatePostEvent(new \App\Entity\Timesheet());
        $listener->triggerWebhook($event);
    }
}
