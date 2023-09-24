<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Controller\HomepageController;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\Event\UserPreferenceEvent;
use App\Form\Type\CalendarViewType;
use App\Form\Type\FavoriteMenuType;
use App\Form\Type\FirstWeekDayType;
use App\Form\Type\InitialViewType;
use App\Form\Type\SkinType;
use App\Form\Type\TimezoneType;
use App\Form\Type\UserLanguageType;
use App\Form\Type\YesNoType;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Length;
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
            (new UserPreference(UserPreference::HOURLY_RATE, 0))
                ->setOrder(100)
                ->setSection('rate')
                ->setType(MoneyType::class)
                ->setEnabled($enableHourlyRate)
                ->setOptions(array_merge($hourlyRateOptions, ['label' => 'hourlyRate']))
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference(UserPreference::INTERNAL_RATE, null))
                ->setOrder(101)
                ->setSection('rate')
                ->setType(MoneyType::class)
                ->setEnabled($enableHourlyRate)
                ->setOptions(array_merge($hourlyRateOptions, ['label' => 'internalRate', 'required' => false]))
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference(UserPreference::TIMEZONE, $timezone))
                ->setOrder(200)
                ->setSection('locale')
                ->setType(TimezoneType::class),

            (new UserPreference(UserPreference::LOCALE, $this->systemConfiguration->getUserDefaultLanguage()))
                ->setOrder(250)
                ->setSection('locale')
                ->setType(UserLanguageType::class),

            (new UserPreference(UserPreference::FIRST_WEEKDAY, User::DEFAULT_FIRST_WEEKDAY))
                ->setOrder(300)
                ->setSection('locale')
                ->setType(FirstWeekDayType::class),

            (new UserPreference(UserPreference::SKIN, $this->systemConfiguration->getUserDefaultTheme()))
                ->setOrder(400)
                ->setSection('theme')
                ->setType(SkinType::class),

            (new UserPreference('update_browser_title', true))
                ->setOrder(550)
                ->setSection('theme')
                ->setType(YesNoType::class),

            (new UserPreference('calendar_initial_view', CalendarViewType::DEFAULT_VIEW))
                ->setOrder(600)
                ->setSection('behaviour')
                ->setType(CalendarViewType::class),

            (new UserPreference('login_initial_view', HomepageController::DEFAULT_ROUTE))
                ->setOrder(700)
                ->setSection('behaviour')
                ->setType(InitialViewType::class),

            (new UserPreference('favorite_routes', ''))
                ->setOrder(710)
                ->setSection('behaviour')
                ->setOptions(['required' => false])
                ->addConstraint(new Length(['max' => 150]))
                ->setType(FavoriteMenuType::class),

            (new UserPreference('daily_stats', false))
                ->setOrder(800)
                ->setSection('behaviour')
                ->setType(YesNoType::class),

            (new UserPreference('export_decimal', false))
                ->setOrder(900)
                ->setSection('behaviour')
                ->setType(YesNoType::class),
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
