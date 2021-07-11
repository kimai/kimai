<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\MailConfiguration;
use App\Entity\User;
use App\Event\DashboardEvent;
use App\Event\EmailEvent;
use App\EventSubscriber\DashboardSubscriber;
use App\EventSubscriber\EmailSubscriber;
use App\Mail\KimaiMailer;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @covers \App\EventSubscriber\EmailSubscriber
 */
class EmailSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = EmailSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(EmailEvent::class, $events);
        $methodName = $events[EmailEvent::class][0];
        $this->assertTrue(method_exists(EmailSubscriber::class, $methodName));
    }

    public function testSendIsTriggered()
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $mailer = new KimaiMailer(
            new MailConfiguration('test@example.com'),
            $mailer
        );

        $sut = new EmailSubscriber($mailer);

        $event = new EmailEvent(new Email());

        $sut->onMailEvent($event);
    }

    public function testWithAdminUser()
    {
        $sut = $this->getSubscriber(true, 13, 28, 37, 5);
        $event = new DashboardEvent(new User());

        $this->assertEquals(0, \count($event->getSections()));
        $sut->onDashboardEvent($event);

        $sections = $event->getSections();
        $widgets = $sections[0]->getWidgets();

        $this->assertEquals(1, \count($sections));
        $this->assertEquals(4, \count($widgets));

        $this->assertEquals('stats.userTotal', $widgets[0]->getTitle());
        $this->assertEquals(13, $widgets[0]->getData());

        $this->assertEquals('stats.customerTotal', $widgets[1]->getTitle());
        $this->assertEquals(5, $widgets[1]->getData());

        $this->assertEquals('stats.projectTotal', $widgets[2]->getTitle());
        $this->assertEquals(37, $widgets[2]->getData());

        $this->assertEquals('stats.activityTotal', $widgets[3]->getTitle());
        $this->assertEquals(28, $widgets[3]->getData());
    }

    protected function getSubscriber(bool $isAdmin, int $userCount, int $activityCount, int $projectCount, int $customerCount)
    {
        $authMock = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();
        $authMock->method('isGranted')->willReturn($isAdmin);

        $userMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $userMock->method('countUsersForQuery')->willReturn($userCount);

        $projectMock = $this->getMockBuilder(ProjectRepository::class)->disableOriginalConstructor()->getMock();
        $projectMock->method('countProjectsForQuery')->willReturn($projectCount);

        $activityMock = $this->getMockBuilder(ActivityRepository::class)->disableOriginalConstructor()->getMock();
        $activityMock->method('countActivitiesForQuery')->willReturn($activityCount);

        $customerMock = $this->getMockBuilder(CustomerRepository::class)->disableOriginalConstructor()->getMock();
        $customerMock->method('countCustomersForQuery')->willReturn($customerCount);

        return new DashboardSubscriber($authMock, $userMock, $activityMock, $projectMock, $customerMock);
    }
}
