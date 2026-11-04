<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\TimesheetMeta;
use App\Event\QuickEntryMetaDisplayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class QuickEntryMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            QuickEntryMetaDisplayEvent::class => ['loadField', 200],
        ];
    }

    public function loadField(QuickEntryMetaDisplayEvent $event): void
    {
        $event->addField(
            (new TimesheetMeta())
                ->setName('location')
                ->setLabel('Location')
                ->setType(TextType::class)
                ->setIsVisible(true)
        );
    }
}
