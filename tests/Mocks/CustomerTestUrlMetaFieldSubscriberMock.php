<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\CustomerMeta;
use App\Event\CustomerMetaDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\Url;

class CustomerTestUrlMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerMetaDefinitionEvent::class => ['loadMeta', 200],
        ];
    }

    public function loadMeta(CustomerMetaDefinitionEvent $event): void
    {
        $definition = (new CustomerMeta())
            ->setName('homepage')
            ->setType(UrlType::class)
            ->addConstraint(new Url())
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }
}
