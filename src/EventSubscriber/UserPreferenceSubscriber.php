<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\Event\UserPreferenceEvent;
use App\Form\Type\CalendarViewType;
use App\Form\Type\LanguageType;
use App\Form\Type\SkinType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
     * @param EventDispatcherInterface $dispatcher
     * @param TokenStorageInterface $storage
     * @param AuthorizationCheckerInterface $voter
     */
    public function __construct(EventDispatcherInterface $dispatcher, TokenStorageInterface $storage, AuthorizationCheckerInterface $voter)
    {
        $this->eventDispatcher = $dispatcher;
        $this->storage = $storage;
        $this->voter = $voter;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PrepareUserEvent::PREPARE => ['loadUserPreferences', 200]
        ];
    }

    /**
     * @param User $user
     * @return UserPreference[]
     */
    public function getDefaultPreferences(User $user)
    {
        $enableHourlyRate = false;

        if ($this->voter->isGranted('hourly-rate', $user)) {
            $enableHourlyRate = true;
        }

        /*
            (new UserPreference())
                ->setName('timezone')
                ->setValue(date_default_timezone_get())
                ->setType(TimezoneType::class),
        */

        return [
            (new UserPreference())
                ->setName(UserPreference::HOURLY_RATE)
                ->setValue(0)
                ->setType(NumberType::class)
                ->setEnabled($enableHourlyRate)
                ->addConstraint(new Range(['min' => 0])),

            (new UserPreference())
                ->setName('timezone')
                ->setValue(date_default_timezone_get())
                ->setType(TimezoneType::class),

            (new UserPreference())
                ->setName('language')
                ->setValue('en') // TODO fetch from services.yaml
                ->setType(LanguageType::class),

            (new UserPreference())
                ->setName(UserPreference::SKIN)
                ->setValue('green')
                ->setType(SkinType::class),

            (new UserPreference())
                ->setName('theme.fixed_layout')
                ->setValue(true)
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('theme.boxed_layout')
                ->setValue(false)
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('theme.collapsed_sidebar')
                ->setValue(false)
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('theme.mini_sidebar')
                ->setValue(true)
                ->setType(CheckboxType::class),

            (new UserPreference())
                ->setName('calendar.initial_view')
                ->setValue(CalendarViewType::DEFAULT_VIEW)
                ->setType(CalendarViewType::class),
        ];
    }

    /**
     * @param PrepareUserEvent $event
     */
    public function loadUserPreferences(PrepareUserEvent $event)
    {
        if (!$this->canHandleEvent($event)) {
            return;
        }

        $user = $event->getUser();

        $prefs = [];
        foreach ($user->getPreferences() as $preference) {
            $prefs[$preference->getName()] = $preference;
        }

        $event = new UserPreferenceEvent($user, $this->getDefaultPreferences($user));
        $this->eventDispatcher->dispatch(UserPreferenceEvent::CONFIGURE, $event);

        foreach ($event->getPreferences() as $preference) {
            /* @var UserPreference[] $prefs */
            if (isset($prefs[$preference->getName()])) {
                /* @var UserPreference $pref */
                $prefs[$preference->getName()]
                    ->setType($preference->getType())
                    ->setConstraints($preference->getConstraints())
                    ->setEnabled($preference->isEnabled())
                ;
            } else {
                $prefs[$preference->getName()] = $preference;
            }
        }

        $user->setPreferences(array_values($prefs));
    }

    /**
     * @param PrepareUserEvent $event
     * @return bool
     */
    protected function canHandleEvent(PrepareUserEvent $event): bool
    {
        if (null === ($user = $event->getUser())) {
            return false;
        }

        return ($user instanceof User);
    }
}
