<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;

class PrepareUserEventSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PrepareUserEvent::class => ['prepareUserEvent', 200],
        ];
    }

    public function prepareUserEvent(PrepareUserEvent $event): void
    {
        $definition = (new UserPreference('metatestmock'))
            ->setType(TextType::class)
            ->addConstraint(new Length(['max' => 200]))
            ->setEnabled(true);

        $event->getUser()->addPreference($definition);

        $definition = (new UserPreference('foobar'))
            ->setType(IntegerType::class)
            ->setEnabled(false);

        $event->getUser()->addPreference($definition);
    }
}
