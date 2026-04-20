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
use App\Serializer\SerializerInterface;
use App\Webhook\WebhookListener;
use App\Webhook\WebhookService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\Event;

#[CoversClass(WebhookListener::class)]
class WebhookListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WebhookListener::getSubscribedEvents();

        self::assertCount(20, $events);

        self::assertArrayHasKey(ActivityCreatePostEvent::class, $events);
        self::assertArrayHasKey(ActivityDeleteEvent::class, $events);
        self::assertArrayHasKey(ActivityUpdatePostEvent::class, $events);
        self::assertArrayHasKey(CustomerCreatePostEvent::class, $events);
        self::assertArrayHasKey(CustomerDeleteEvent::class, $events);
        self::assertArrayHasKey(CustomerUpdatePostEvent::class, $events);
        self::assertArrayHasKey(InvoiceCreatedEvent::class, $events);
        self::assertArrayHasKey(InvoiceDeleteEvent::class, $events);
        self::assertArrayHasKey(ProjectCreatePostEvent::class, $events);
        self::assertArrayHasKey(ProjectDeleteEvent::class, $events);
        self::assertArrayHasKey(ProjectUpdatePostEvent::class, $events);
        self::assertArrayHasKey(TimesheetCreatePostEvent::class, $events);
        self::assertArrayHasKey(TimesheetStopPostEvent::class, $events);
        self::assertArrayHasKey(TimesheetUpdatePostEvent::class, $events);
        self::assertArrayHasKey(UserCreatePostEvent::class, $events);
        self::assertArrayHasKey(UserDeletePostEvent::class, $events);
        self::assertArrayHasKey(UserUpdatePostEvent::class, $events);
        self::assertArrayHasKey(TeamCreatePostEvent::class, $events);
        self::assertArrayHasKey(TeamDeleteEvent::class, $events);
        self::assertArrayHasKey(TeamUpdatePostEvent::class, $events);

        foreach ($events as $event) {
            self::assertEquals('triggerWebhook', $event[0]);
            self::assertEquals(1000, $event[1]);
        }
    }

    public function testSubscribedEventsMethodExists(): void
    {
        $events = WebhookListener::getSubscribedEvents();
        foreach ($events as $event) {
            self::assertTrue(
                method_exists(WebhookListener::class, $event[0]),
                \sprintf('Method "%s" does not exist', $event[0])
            );
        }
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

        $webhookService = new WebhookService($systemConfig, $serializer, $bus, new NullLogger(), new MockHttpClient());

        return new WebhookListener($webhookService, new NullLogger());
    }

    /**
     * @param list<array{url: string, secret?: string, events: list<string>}> $endpoints
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
     * @return array<string, array{string, Event}>
     */
    public static function getEventsByEntityType(): array
    {
        return [
            'timesheet' => ['timesheet', new TimesheetCreatePostEvent(new \App\Entity\Timesheet())],
            'customer' => ['customer', new CustomerCreatePostEvent(new \App\Entity\Customer('Test'))],
            'project' => ['project', new ProjectCreatePostEvent(new \App\Entity\Project())],
            'activity' => ['activity', new ActivityCreatePostEvent(new \App\Entity\Activity())],
            'user' => ['user', new UserCreatePostEvent(new \App\Entity\User())],
            'team' => ['team', new TeamCreatePostEvent(new \App\Entity\Team('Test'))],
        ];
    }

    #[DataProvider('getEventsByEntityType')]
    public function testEventNotFiredWhenEntityNotSubscribed(string $entityType, Event $event): void
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

    #[DataProvider('getEventsByEntityType')]
    public function testEventFiredWhenEntitySubscribed(string $entityType, Event $event): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects(self::once())->method('toArray')->willReturn(['id' => 1]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $listener = $this->createListener($this->settings([
            ['url' => 'https://example.com/webhook', 'secret' => 'secret', 'events' => [$entityType]],
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
