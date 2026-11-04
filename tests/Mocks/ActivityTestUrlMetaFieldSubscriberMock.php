<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\ActivityMeta;
use App\Event\ActivityMetaDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Url;

class ActivityTestUrlMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ActivityMetaDefinitionEvent::class => ['loadMeta', 200],
        ];
    }

    public function loadMeta(ActivityMetaDefinitionEvent $event): void
    {
        $definition = (new ActivityMeta())
            ->setName('homepage')
            ->setType(UrlType::class)
            ->addConstraint(new Url())
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }
}
