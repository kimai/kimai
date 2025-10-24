<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\tests\Service;

use App\Entity\Project;
use App\Repository\TimesheetRepository;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\CustomerPortalBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\CustomerPortalBundle\Service\ViewService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class ViewServiceTest extends TestCase
{
    private ViewService $service;
    private SessionInterface $session;
    private PasswordHasherInterface|MockObject $encoder;
    private string $sessionKey;
    private Request $request;

    protected function setUp(): void
    {
        $timesheetRepository = $this->createMock(TimesheetRepository::class);
        $sharedProjectTimesheetRepository = $this->createMock(SharedProjectTimesheetRepository::class);
        $requestStack = new RequestStack();
        $this->session = new Session(new MockArraySessionStorage());

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->encoder = $this->createMock(PasswordHasherInterface::class);
        $factory->method('getPasswordHasher')->willReturn($this->encoder);

        $this->request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '1.1.1.1']);
        $this->request->setSession($this->session);
        $requestStack->push($this->request);

        $rateLimiter = new RateLimiterFactory(['id' => 'customer_portal', 'policy' => 'sliding_window', 'limit' => 5, 'interval' => '1 hour'], new InMemoryStorage());

        $this->service = new ViewService($timesheetRepository, $requestStack, $factory, $sharedProjectTimesheetRepository, $rateLimiter);
    }

    private function createSharedProjectTimesheet(): SharedProjectTimesheet
    {
        $project = $this->createMock(Project::class);
        $project->method('getId')
            ->willReturn(1);

        $tmp = new SharedProjectTimesheet();
        $tmp->setProject($project);
        $tmp->setShareKey('sharekey');

        return $tmp;
    }

    public function testNoPassword(): void
    {
        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, '', $this->request);
        self::assertTrue($hasAccess);
    }

    public function testValidPassword(): void
    {
        $this->encoder->method('verify')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('password');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'password', $this->request);
        self::assertTrue($hasAccess);
    }

    public function testInvalidPassword(): void
    {
        $this->encoder->method('verify')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('password');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'wrong', $this->request);
        self::assertFalse($hasAccess);
    }

    public function testPasswordRemember(): void
    {
        // Expect the encoder->verify method is called only once
        $this->encoder->expects($this->exactly(1))
            ->method('verify')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('test');

        self::assertFalse($this->service->hasAccess($sharedProjectTimesheet, null, $this->request));
        self::assertTrue($this->service->hasAccess($sharedProjectTimesheet, 'test', $this->request));
        self::assertTrue($this->service->hasAccess($sharedProjectTimesheet, null, $this->request));
    }

    public function testPasswordChange(): void
    {
        // Expect the encoder->verify method is called only once
        $this->encoder->expects($this->exactly(2))
            ->method('verify')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('test');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'test', $this->request);
        self::assertTrue($hasAccess);

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('changed');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'test', $this->request);
        self::assertFalse($hasAccess);
    }
}
