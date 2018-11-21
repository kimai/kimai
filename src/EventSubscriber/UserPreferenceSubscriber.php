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
use App\Event\UserPreferenceEvent;
use App\Form\Type\CalendarViewType;
use App\Form\Type\LanguageType;
use App\Form\Type\SkinType;
use App\Security\RolePermissionManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Range;

class UserPreferenceSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TokenStorageInterface
     */
    protected $storage;

    /**
     * @var RolePermissionManager
     */
    protected $acl;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param TokenStorageInterface $storage
     * @param RolePermissionManager $acl
     */
    public function __construct(EventDispatcherInterface $dispatcher, TokenStorageInterface $storage, RolePermissionManager $acl)
    {
        $this->eventDispatcher = $dispatcher;
        $this->storage = $storage;
        $this->acl = $acl;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['loadUserPreferences', 200]
        ];
    }

    /**
     * @param User $user
     * @return UserPreference[]
     */
    public function getDefaultPreferences(User $user)
    {
        $enableHourlyRate = false;
        foreach ($user->getRoles() as $role) {
            if ($this->acl->hasPermission($role, 'edit_own_hourly_rate')) {
                $enableHourlyRate = true;
            }
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
                ->setType(IntegerType::class)
                ->setEnabled($enableHourlyRate)
                ->addConstraint(new Range(['min' => 0])),

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
     * @param KernelEvent $event
     */
    public function loadUserPreferences(KernelEvent $event)
    {
        if (!$this->canHandleEvent($event)) {
            return;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

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
     * @param KernelEvent $event
     * @return bool
     */
    protected function canHandleEvent(KernelEvent $event): bool
    {
        // Ignore sub-requests
        if (!$event->isMasterRequest()) {
            return false;
        }

        // ignore events like the toolbar where we do not have a token
        if (null === $this->storage->getToken()) {
            return false;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        return ($user instanceof User);
    }
}
