<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\FormConfiguration;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\Event\UserPreferenceEvent;
use App\Form\Type\CalendarViewType;
use App\Form\Type\InitialViewType;
use App\Form\Type\LanguageType;
use App\Form\Type\SkinType;
use App\Form\Type\ThemeLayoutType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Range;

class UserPreferenceSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $voter;
    /**
     * @var TokenStorageInterface
     */
    protected $storage;
    /**
     * @var FormConfiguration
     */
    protected $formConfig;

    public function __construct(EventDispatcherInterface $dispatcher, TokenStorageInterface $storage, AuthorizationCheckerInterface $voter, FormConfiguration $formConfig)
    {
        $this->eventDispatcher = $dispatcher;
        $this->storage = $storage;
        $this->voter = $voter;
        $this->formConfig = $formConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PrepareUserEvent::class => ['loadUserPreferences', 200]
        ];
    }

    private function getDefaultTheme(): ?string
    {
        return $this->formConfig->getUserDefaultTheme();
    }

    private function getDefaultCurrency(): string
    {
        return $this->formConfig->getUserDefaultCurrency();
    }

    private function getDefaultLanguage(): string
    {
        return $this->formConfig->getUserDefaultLanguage();
    }

    private function getDefaultTimezone(): string
    {
        $timezone = $this->formConfig->getUserDefaultTimezone();
        if (null === $timezone) {
            $timezone = date_default_timezone_get();
        }

        return $timezone;
    }

    /**
     * @param User $user
     * @return UserPreference[]
     */
    public function getDefaultPreferences(User $user)
    {
        $enableHourlyRate = false;
        $hourlyRateOptions = [];

        if ($this->voter->isGranted('hourly-rate', $user)) {
            $enableHourlyRate = true;
            $hourlyRateOptions = ['currency' => $this->getDefaultCurrency()];
        }

        return [
            (new UserPreference())
                ->setName(UserPreference::HOURLY_RATE)
                ->setValue(0)
                ->setOrder(100)
                ->setType(MoneyType::class)
                ->setEnabled($enableHourlyRate)
                ->setOptions($hourlyRateOptions)
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference())
                ->setName(UserPreference::INTERNAL_RATE)
                ->setValue(null)
                ->setOrder(101)
                ->setType(MoneyType::class)
                ->setEnabled($enableHourlyRate)
                ->setOptions(array_merge($hourlyRateOptions, ['label' => 'label.rate_internal', 'required' => false]))
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference())
                ->setName(UserPreference::TIMEZONE)
                ->setValue($this->getDefaultTimezone())
                ->setOrder(200)
                ->setType(TimezoneType::class),

            (new UserPreference())
                ->setName(UserPreference::LOCALE)
                ->setValue($this->getDefaultLanguage())
                ->setOrder(300)
                ->setType(LanguageType::class),

            (new UserPreference())
                ->setName(UserPreference::SKIN)
                ->setValue($this->getDefaultTheme())
                ->setOrder(400)
                ->setType(SkinType::class),

            (new UserPreference())
                ->setName('theme.layout')
                ->setValue('fixed')
                ->setOrder(450)
                ->setType(ThemeLayoutType::class),

            (new UserPreference())
                ->setName('theme.collapsed_sidebar')
                ->setValue(false)
                ->setOrder(500)
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('calendar.initial_view')
                ->setValue(CalendarViewType::DEFAULT_VIEW)
                ->setOrder(600)
                ->setType(CalendarViewType::class),

            (new UserPreference())
                ->setName('login.initial_view')
                ->setValue(InitialViewType::DEFAULT_VIEW)
                ->setOrder(700)
                ->setType(InitialViewType::class),

            (new UserPreference())
                ->setName('timesheet.daily_stats')
                ->setValue(false)
                ->setOrder(800)
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('timesheet.export_decimal')
                ->setValue(false)
                ->setOrder(900)
                ->setType(CheckboxType::class),
        ];
    }

    /**
     * @param PrepareUserEvent $event
     */
    public function loadUserPreferences(PrepareUserEvent $event)
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
                ;
            } else {
                $user->addPreference($preference);
            }
        }
    }
}
