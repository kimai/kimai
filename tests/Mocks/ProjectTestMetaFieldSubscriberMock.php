<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;

class ProjectTestMetaFieldSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProjectMetaDefinitionEvent::class => ['loadMeta', 200],
        ];
    }

    public function loadMeta(ProjectMetaDefinitionEvent $event)
    {
        $definition = (new ProjectMeta())
            ->setName('metatestmock')
            ->setType(TextType::class)
            ->addConstraint(new Length(['max' => 200]))
            ->setIsVisible(true);

        $event->getEntity()->setMetaField($definition);
    }
}
