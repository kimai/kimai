<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\ActivityMeta;
use App\Entity\CustomerMeta;
use App\Entity\MetaTableTypeInterface;
use App\Entity\ProjectMeta;
use App\Entity\TimesheetMeta;
use App\Entity\UserPreference;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class MetaFieldColumnSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDisplayEvent::class => ['loadTimesheetField', 200],
            CustomerMetaDisplayEvent::class => ['loadCustomerField', 200],
            ProjectMetaDisplayEvent::class => ['loadProjectField', 200],
            ActivityMetaDisplayEvent::class => ['loadActivityField', 200],
            UserPreferenceDisplayEvent::class => ['loadUserField', 200],
        ];
    }

    public function loadTimesheetField(TimesheetMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new TimesheetMeta(), 'foo'));
        $event->addField($this->prepareEntity(new TimesheetMeta(), 'foo2'));
    }

    public function loadCustomerField(CustomerMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new CustomerMeta(), 'customer-foo'));
    }

    public function loadProjectField(ProjectMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new ProjectMeta(), 'project-foo'));
        $event->addField($this->prepareEntity(new ProjectMeta(), 'project-foo2')->setIsVisible(false));
    }

    public function loadActivityField(ActivityMetaDisplayEvent $event): void
    {
        $event->addField($this->prepareEntity(new ActivityMeta(), 'activity-foo'));
    }

    public function loadUserField(UserPreferenceDisplayEvent $event): void
    {
        $event->addPreference(new UserPreference('mypref', 'hello world'));
    }

    private function prepareEntity(MetaTableTypeInterface $meta, string $name): MetaTableTypeInterface
    {
        return $meta
            ->setLabel('Working place')
            ->setName($name)
            ->setType(TextType::class)
            ->setIsVisible(true);
    }
}
