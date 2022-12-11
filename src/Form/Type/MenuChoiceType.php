<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Event\ConfigureMainMenuEvent;
use App\Reporting\ReportingService;
use App\Utils\MenuItemModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MenuChoiceType extends AbstractType
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private ReportingService $reportingService, private TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'include_reports' => true,
            'filter_menus' => [],
        ]);

        $resolver->setDefault('choices', function (Options $options): array {
            /** @var User $user */
            $user = $options['user'];

            return $this->getChoices($user, $options['include_reports'], $options['filter_menus']);
        });
    }

    /**
     * @param User $user
     * @param bool $withReports
     * @param array<string> $filter
     * @return array<string, string>
     */
    private function getChoices(User $user, bool $withReports, array $filter): array
    {
        $event = new ConfigureMainMenuEvent();
        $this->eventDispatcher->dispatch($event);

        $choices = $this->getChoicesFromMenu($event->getMenu(), $filter);
        $choices += $this->getChoicesFromMenu($event->getAppsMenu(), $filter);
        $choices += $this->getChoicesFromMenu($event->getAdminMenu(), $filter);
        $choices += $this->getChoicesFromMenu($event->getSystemMenu(), $filter);

        if ($withReports) {
            foreach ($this->reportingService->getAvailableReports($user) as $report) {
                $label = $this->translator->trans('menu.reporting') . ': ' . $this->translator->trans($report->getLabel(), [], 'reporting');
                $choices[$label] = $report->getId();
            }
        }

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
                if (\count($child->getRouteArgs()) === 0) {
                    $choices[$child->getLabel()] = $child->getIdentifier();
                }
                continue;
            }
            foreach ($child->getChildren() as $subChild) {
                if (\count($subChild->getRouteArgs()) === 0) {
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
