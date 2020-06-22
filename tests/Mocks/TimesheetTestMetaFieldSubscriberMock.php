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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;

class TimesheetTestMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDefinitionEvent::class => ['loadMeta', 200],
        ];
    }

    public function loadMeta(TimesheetMetaDefinitionEvent $event)
    {
        $definition = (new TimesheetMeta())
            ->setName('metatestmock')
            ->setType(TextType::class)
            ->addConstraint(new Length(['max' => 200]))
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }
}
