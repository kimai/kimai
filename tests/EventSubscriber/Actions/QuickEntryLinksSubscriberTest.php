<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\Entity\User;
use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\QuickEntryLinksSubscriber;
use App\Form\Model\DateRange;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\TimesheetQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(QuickEntryLinksSubscriber::class)]
class QuickEntryLinksSubscriberTest extends AbstractActionsSubscriberTestCase
{
    public function testEventName(): void
    {
        $this->assertGetSubscribedEvent(QuickEntryLinksSubscriber::class, 'quick_entry_links');
    }

    /**
     * @param array<string> $permissions
     */
    private function createQuickEntrySubscriber(array $permissions): QuickEntryLinksSubscriber
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnCallback(
            function (mixed $attribute, mixed $subject = null) use ($permissions): bool {
                return \in_array($attribute, $permissions, true);
            }
        );

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(
            function (string $route, array $parameters = []): string {
                if (\count($parameters) === 0) {
                    return $route;
                }

                return $route . '?' . http_build_query($parameters);
            }
        );

        return new QuickEntryLinksSubscriber($auth, $router);
    }

    private function createUser(int $id = 1): User
    {
        $user = new User();
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setValue($user, $id);

        return $user;
    }

    /**
     * @param array<mixed> $payload
     * @param array<string> $permissions
     * @return array<string, array<mixed>>
     */
    private function getActions(array $payload, array $permissions, ?string $locale = null): array
    {
        $sut = $this->createQuickEntrySubscriber($permissions);
        $event = new PageActionsEvent(new User(), $payload, 'quick_entry_links', 'index');
        $event->setLocale($locale);
        $sut->handleEvent($event);

        return $event->getActions();
    }

    public function testWithoutUserInPayload(): void
    {
        self::assertEquals([], $this->getActions([], ['view_other_timesheet']));
        self::assertEquals([], $this->getActions(['query' => $this->createQuery('2026-01-01', '2026-01-31')], ['view_other_timesheet']));
    }

    public function testWithInvalidUserInPayload(): void
    {
        $invalid = [
            'null' => null,
            'string' => 'user',
            'int' => 1,
            'array' => ['id' => 1],
            'object' => new \stdClass(),
        ];

        foreach ($invalid as $name => $value) {
            self::assertEquals([], $this->getActions(['user' => $value], ['view_other_timesheet']), \sprintf('Failed for payload type "%s"', $name));
        }
    }

    public function testWithoutPermission(): void
    {
        self::assertEquals([], $this->getActions(['user' => $this->createUser()], []));
        self::assertEquals([], $this->getActions(['user' => $this->createUser()], ['view_own_timesheet']));
    }

    public function testFilterWithUser(): void
    {
        $actions = $this->getActions(['user' => $this->createUser(5)], ['view_other_timesheet']);

        self::assertEquals(['filter'], array_keys($actions));
        self::assertEquals(
            ['title' => 'timesheet.filter', 'url' => 'admin_timesheet?' . http_build_query(['users' => [5]]), 'icon' => 'timesheet-team'],
            $actions['filter']
        );
    }

    private function createQuery(string $begin, string $end): TimesheetQuery
    {
        $dateRange = new DateRange();
        $dateRange->setBegin(new \DateTime($begin));
        $dateRange->setEnd(new \DateTime($end));

        $query = new TimesheetQuery();
        $query->setDateRange($dateRange);

        return $query;
    }

    public function testFilterWithDaterange(): void
    {
        $actions = $this->getActions(['user' => $this->createUser(5), 'query' => $this->createQuery('2026-01-01', '2026-01-31')], ['view_other_timesheet']);

        self::assertEquals(
            ['title' => 'timesheet.filter', 'url' => 'admin_timesheet?' . http_build_query(['users' => [5], 'daterange' => '2026-01-01 - 2026-01-31']), 'icon' => 'timesheet-team'],
            $actions['filter']
        );
    }

    public function testFilterIgnoresIncompleteDaterange(): void
    {
        $beginOnly = new TimesheetQuery();
        $beginOnly->getDateRange()?->setBegin(new \DateTime('2026-01-01'));

        $endOnly = new TimesheetQuery();
        $endOnly->getDateRange()?->setEnd(new \DateTime('2026-01-31'));

        $queries = [
            'empty' => new TimesheetQuery(),
            'begin-only' => $beginOnly,
            'end-only' => $endOnly,
        ];

        foreach ($queries as $name => $query) {
            $actions = $this->getActions(['user' => $this->createUser(5), 'query' => $query], ['view_other_timesheet']);

            self::assertEquals('admin_timesheet?' . http_build_query(['users' => [5]]), $actions['filter']['url'], \sprintf('Failed for query "%s"', $name));
        }
    }

    public function testFilterIgnoresInvalidQuery(): void
    {
        $invalid = [
            'null' => null,
            'string' => '2026-01-01 - 2026-01-31',
            'array' => ['2026-01-01'],
            'datetime' => new \DateTime(),
            'daterange' => (new DateRange())->setBegin(new \DateTime('2026-01-01'))->setEnd(new \DateTime('2026-01-31')),
            'other-query' => new CustomerQuery(),
        ];

        foreach ($invalid as $name => $query) {
            $actions = $this->getActions(['user' => $this->createUser(5), 'query' => $query], ['view_other_timesheet']);

            self::assertEquals('admin_timesheet?' . http_build_query(['users' => [5]]), $actions['filter']['url'], \sprintf('Failed for query type "%s"', $name));
        }
    }

    public function testFilterIgnoresLegacyDaterangeKey(): void
    {
        $actions = $this->getActions(['user' => $this->createUser(5), 'daterange' => '2026-01-01 - 2026-01-31'], ['view_other_timesheet']);

        self::assertEquals('admin_timesheet?' . http_build_query(['users' => [5]]), $actions['filter']['url']);
    }

    public function testFilterWithDaterangeAndLocale(): void
    {
        $actions = $this->getActions(['user' => $this->createUser(5), 'query' => $this->createQuery('2026-02-01', '2026-02-28')], ['view_other_timesheet'], 'de');

        self::assertEquals(
            'admin_timesheet?' . http_build_query(['users' => [5], 'daterange' => '2026-02-01 - 2026-02-28', '_locale' => 'de']),
            $actions['filter']['url']
        );
    }

    public function testFilterIncludesLocale(): void
    {
        $actions = $this->getActions(['user' => $this->createUser(5)], ['view_other_timesheet'], 'de');

        self::assertEquals('admin_timesheet?' . http_build_query(['users' => [5], '_locale' => 'de']), $actions['filter']['url']);
    }
}
