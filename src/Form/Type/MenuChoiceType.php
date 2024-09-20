<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MenuChoiceType extends AbstractType
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'filter_menus' => [],
        ]);

        $resolver->setDefault('choices', function (Options $options): array {
            return $this->getChoices($options['filter_menus']);
        });
    }

    /**
     * @param array<string> $filter
     * @return array<string, string>
     */
    private function getChoices(array $filter): array
    {
        $event = new ConfigureMainMenuEvent();
        $this->eventDispatcher->dispatch($event);

        $choices = $this->getChoicesFromMenu($event->getMenu(), $filter);
        $choices += $this->getChoicesFromMenu($event->getAppsMenu(), $filter); // @phpstan-ignore-line
        $choices += $this->getChoicesFromMenu($event->getAdminMenu(), $filter);
        $choices += $this->getChoicesFromMenu($event->getSystemMenu(), $filter);

        return $choices;
    }

    /**
     * @param MenuItemModel $menu
     * @param array<string> $filter
     * @return array<string, string>
     */
    private function getChoicesFromMenu(MenuItemModel $menu, array $filter): array
    {
        $choices = [];

        foreach ($menu->getChildren() as $child) {
            if (\in_array($child->getIdentifier(), $filter)) {
                continue;
            }
            if (!$child->hasChildren()) {
                if (\count($child->getRouteArgs()) === 0 && $child->getRoute() !== null) {
                    $choices[$child->getLabel()] = $child->getIdentifier();
                }
                continue;
            }
            foreach ($child->getChildren() as $subChild) {
                if (\count($subChild->getRouteArgs()) === 0 && $subChild->getRoute() !== null) {
                    $choices[$subChild->getLabel()] = $subChild->getIdentifier();
                }
            }
        }

        return $choices;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
