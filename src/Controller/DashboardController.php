<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Utils\PageSetup;
use App\Widget\DashboardService;
use App\Widget\WidgetInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dashboard controller for the admin area.
 *
 * Changing the dashboard is done through the API, see App\API\DashboardController.
 */
#[Route(path: '/dashboard')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * @param array<WidgetInterface> $widgets
     * @return array<WidgetInterface>
     */
    private function filterWidgets(array $widgets, User $user): array
    {
        $filteredWidgets = [];

        foreach ($this->dashboardService->getUserWidgets($user) as $setting) {
            $id = $setting['id'];
            $options = $setting['options'];
            foreach ($widgets as $widget) {
                if ($widget->getId() === $id) {
                    $tmpWidget = clone $widget;
                    foreach ($options as $key => $value) {
                        $tmpWidget->setOption($key, $value);
                    }
                    $filteredWidgets[] = $tmpWidget;
                    break;
                }
            }
        }

        return $filteredWidgets;
    }

    #[Route(path: '/', defaults: [], name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $available = $this->dashboardService->getAvailableWidgets($user);
        $widgets = $this->filterWidgets($available, $user);

        $page = new PageSetup('dashboard.title');
        $page->setHelp('dashboard.html');
        $page->setActionName('dashboard');
        $page->setActionPayload(['widgets' => $widgets, 'available' => $available]);

        return $this->render('dashboard/index.html.twig', [
            'page_setup' => $page,
            'widgets' => $widgets,
            'available' => $available,
        ]);
    }

    #[Route(path: '/edit/', defaults: [], name: 'dashboard_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        $available = $this->dashboardService->getAvailableWidgets($user);
        $widgets = $this->filterWidgets($available, $user);

        $choices = [];

        // the list of widgets for the dropdown is created in the EventListener and not here
        // this form is mainly used for saving
        foreach ($available as $widget) {
            if (empty($widget->getTitle())) {
                continue;
            }
            $choices[$widget->getId()] = $widget->getId();
        }

        $form = $this->createFormBuilder(null, [])
            ->add('widgets', ChoiceType::class, ['choices' => $choices, 'multiple' => true])
            ->setAction($this->generateUrl('dashboard_edit'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $userWidgets = $this->dashboardService->getUserWidgets($user);
                $saveWidgets = [];
                foreach ($form->getData()['widgets'] as $widgetId) {
                    $options = [];
                    foreach ($userWidgets as $setting) {
                        if ($setting['id'] === $widgetId) {
                            $options = $setting['options'];
                        }
                    }
                    $saveWidgets[] = ['id' => $widgetId, 'options' => $options];
                }

                $this->dashboardService->saveUserWidgets($user, $saveWidgets);

                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('dashboard');
            } catch (\Exception $ex) {
                $this->flashDeleteException($ex);
            }
        }

        $page = new PageSetup('dashboard.title');
        $page->setHelp('dashboard.html');
        $page->setActionName('dashboard');
        $page->setActionView('edit');
        $page->setActionPayload(['widgets' => $widgets, 'available' => $available]);

        return $this->render('dashboard/grid.html.twig', [
            'page_setup' => $page,
            'widgets' => $widgets,
            'form' => $form->createView(),
        ]);
    }
}
