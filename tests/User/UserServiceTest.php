<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\User;

use App\Entity\User;
use App\Entity\UserPreference;
use App\EventSubscriber\WorkContractPreferenceSubscriber;
use App\Repository\UserRepository;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\User\UserService;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(UserService::class)]
class UserServiceTest extends TestCase
{
    public function testCreateNewUserAttachesWorkContractPreferencesFromSubscriber(): void
    {
        $dispatcher = new EventDispatcher();
        $voter = $this->createMock(AuthorizationCheckerInterface::class);
        $voter->method('isGranted')->with('contract', self::isInstanceOf(User::class))->willReturn(true);
        $dispatcher->addSubscriber(new WorkContractPreferenceSubscriber($voter));

        $sut = new UserService(
            $this->createMock(UserRepository::class),
            $dispatcher,
            $this->createMock(ValidatorInterface::class),
            SystemConfigurationFactory::createStub([
                'defaults' => [
                    'user' => [
                        'timezone' => 'Europe/Berlin',
                        'language' => 'de',
                        'theme' => 'dark',
                    ],
                ],
            ]),
            $this->createMock(UserPasswordHasherInterface::class)
        );

        $user = $sut->createNewUser();

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isEnabled());
        self::assertTrue($user->isRegularUserOnly());
        self::assertSame([User::ROLE_USER], $user->getRoles());
        self::assertSame('Europe/Berlin', $user->getTimezone());
        self::assertSame('de', $user->getLanguage());
        self::assertSame('dark', $user->getSkin());

        self::assertSame(WorkingTimeModeNone::ID, $user->getWorkContractMode());
        self::assertSame(WorkingTimeModeNone::ID, $user->getPreferenceValue(UserPreference::WORK_CONTRACT_TYPE));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY));
        self::assertNull($user->getPreferenceValue(UserPreference::PUBLIC_HOLIDAY_GROUP, 'foo'));
        self::assertSame('0', $user->getPreferenceValue(UserPreference::HOLIDAYS_PER_YEAR));
        self::assertNull($user->getPreferenceValue('work_start_day', 'foo'));
        self::assertNull($user->getPreferenceValue('work_last_day', 'foo'));
    }
}
