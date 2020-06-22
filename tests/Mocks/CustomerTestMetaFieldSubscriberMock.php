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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;

class CustomerTestMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerMetaDefinitionEvent::class => ['loadMeta', 200],
        ];
    }

    public function loadMeta(CustomerMetaDefinitionEvent $event)
    {
        $definition = (new CustomerMeta())
            ->setName('metatestmock')
            ->setType(TextType::class)
            ->addConstraint(new Length(['max' => 200]))
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }
}
