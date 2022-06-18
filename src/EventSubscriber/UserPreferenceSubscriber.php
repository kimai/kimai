<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\Event\UserPreferenceEvent;
use App\Form\Type\CalendarViewType;
use App\Form\Type\FirstWeekDayType;
use App\Form\Type\InitialViewType;
use App\Form\Type\LanguageType;
use App\Form\Type\SkinType;
use App\Form\Type\ThemeLayoutType;
use App\Form\Type\TimezoneType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Range;

final class UserPreferenceSubscriber implements EventSubscriberInterface
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private AuthorizationCheckerInterface $voter, private SystemConfiguration $systemConfiguration)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PrepareUserEvent::class => ['loadUserPreferences', 200]
        ];
    }

    /**
     * @param User $user
     * @return UserPreference[]
     */
    public function getDefaultPreferences(User $user): array
    {
        $timezone = $this->systemConfiguration->getUserDefaultTimezone();
        if (null === $timezone) {
            $timezone = date_default_timezone_get();
        }

        $enableHourlyRate = false;
        $hourlyRateOptions = [];

        if ($this->voter->isGranted('hourly-rate', $user)) {
            $enableHourlyRate = true;
            $hourlyRateOptions = ['currency' => $this->systemConfiguration->getUserDefaultCurrency()];
        }

        return [
            (new UserPreference())
                ->setName(UserPreference::HOURLY_RATE)
                ->setValue(0)
                ->setOrder(100)
                ->setSection('rate')
                ->setType(MoneyType::class)
                ->setEnabled($enableHourlyRate)
                ->setOptions(array_merge($hourlyRateOptions, ['label' => 'label.hourlyRate']))
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference())
                ->setName(UserPreference::INTERNAL_RATE)
                ->setValue(null)
                ->setOrder(101)
                ->setSection('rate')
                ->setType(MoneyType::class)
                ->setEnabled($enableHourlyRate)
                ->setOptions(array_merge($hourlyRateOptions, ['label' => 'label.internalRate', 'required' => false]))
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference())
                ->setName(UserPreference::TIMEZONE)
                ->setValue($timezone)
                ->setOrder(200)
                ->setSection('locale')
                ->setType(TimezoneType::class),

            (new UserPreference())
                ->setName(UserPreference::LOCALE)
                ->setValue($this->systemConfiguration->getUserDefaultLanguage())
                ->setOrder(250)
                ->setSection('locale')
                ->setType(LanguageType::class),

            (new UserPreference())
                ->setName(UserPreference::FIRST_WEEKDAY)
                ->setValue(User::DEFAULT_FIRST_WEEKDAY)
                ->setOrder(300)
                ->setSection('locale')
                ->setType(FirstWeekDayType::class),

            (new UserPreference())
                ->setName(UserPreference::HOUR_24)
                ->setValue(true)
                ->setOrder(305)
                ->setSection('locale')
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName(UserPreference::SKIN)
                ->setValue($this->systemConfiguration->getUserDefaultTheme())
                ->setOrder(400)
                ->setSection('theme')
                ->setType(SkinType::class),

            (new UserPreference())
                ->setName('theme.layout')
                ->setValue('boxed')
                ->setOrder(450)
                ->setSection('theme')
                ->setType(ThemeLayoutType::class),
/*
            (new UserPreference())
                ->setName('theme.collapsed_sidebar')
                ->setValue(true)
                ->setOrder(500)
                ->setSection('theme')
                ->setType(CheckboxType::class),
*/
            (new UserPreference())
                ->setName('theme.update_browser_title')
                ->setValue(true)
                ->setOrder(550)
                ->setSection('theme')
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('calendar.initial_view')
                ->setValue(CalendarViewType::DEFAULT_VIEW)
                ->setOrder(600)
                ->setSection('behaviour')
                ->setType(CalendarViewType::class),

            (new UserPreference())
                ->setName('login.initial_view')
                ->setValue(InitialViewType::DEFAULT_VIEW)
                ->setOrder(700)
                ->setSection('behaviour')
                ->setType(InitialViewType::class),

            (new UserPreference())
                ->setName('timesheet.daily_stats')
                ->setValue(false)
                ->setOrder(800)
                ->setSection('behaviour')
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('timesheet.export_decimal')
                ->setValue(false)
                ->setOrder(900)
                ->setSection('behaviour')
                ->setType(CheckboxType::class),
        ];
    }

    public function loadUserPreferences(PrepareUserEvent $event): void
    {
        $user = $event->getUser();

        $event = new UserPreferenceEvent($user, $this->getDefaultPreferences($user));
        $this->eventDispatcher->dispatch($event);

        foreach ($event->getPreferences() as $preference) {
            $userPref = $user->getPreference($preference->getName());
            if (null !== $userPref) {
                $userPref
                    ->setType($preference->getType())
                    ->setConstraints($preference->getConstraints())
                    ->setEnabled($preference->isEnabled())
                    ->setOptions($preference->getOptions())
                    ->setOrder($preference->getOrder())
                    ->setSection($preference->getSection())
                ;
            } else {
                $user->addPreference($preference);
            }
        }
    }
}
