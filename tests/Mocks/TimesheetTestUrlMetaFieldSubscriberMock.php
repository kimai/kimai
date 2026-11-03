<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\TimesheetMeta;
use App\Event\TimesheetMetaDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Url;

class TimesheetTestUrlMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDefinitionEvent::class => ['loadMeta', 200],
        ];
    }

    public function loadMeta(TimesheetMetaDefinitionEvent $event): void
    {
        $definition = (new TimesheetMeta())
            ->setName('homepage')
            ->setType(UrlType::class)
            ->addConstraint(new Url())
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }
}
