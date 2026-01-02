<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Bookmark;
use App\Entity\User;
use App\Event\DashboardEvent;
use App\Repository\BookmarkRepository;
use App\Utils\PageSetup;
use App\Widget\WidgetInterface;
use App\Widget\WidgetService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Json;

/**
 * Dashboard controller for the admin area.
 */
#[Route(path: '/dashboard')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class DashboardController extends AbstractController
{
    public const BOOKMARK_TYPE = 'dashboard';
    public const BOOKMARK_NAME = 'default';

    /**
     * @var WidgetInterface[]|null
     */
    private ?array $widgets = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WidgetService $service,
        private readonly BookmarkRepository $repository
    )
    {
    }

    /**
     * @return array<WidgetInterface>
     */
    private function getAllAvailableWidgets(User $user): array
    {
        if ($this->widgets === null) {
            $all = [];
            foreach ($this->service->getAllWidgets() as $widget) {
                $widget->setUser($user);

                $permissions = $widget->getPermissions();
                if (\count($permissions) > 0) {
                    $add = false;
                    foreach ($permissions as $perm) {
                        if ($this->isGranted($perm)) {
                            $add = true;
                            break;
                        }
                    }

                    if (!$add) {
                        continue;
                    }
                }
                $all[] = $widget;
            }
            $this->widgets = $all;
        }

        return $this->widgets;
    }

    private function getBookmark(User $user): ?Bookmark
    {
        return $this->repository->findBookmark($user, self::BOOKMARK_TYPE, self::BOOKMARK_NAME);
    }

    private function getDefaultConfig(): array
    {
        $event = new DashboardEvent($this->getUser());

        // default widgets
        $dashboard = [
            // FIXME
            'PaginatedWorkingTimeChart',
        ];

        foreach ($dashboard as $widgetName) {
            $event->addWidget($widgetName);
        }

        $this->eventDispatcher->dispatch($event);

        return $event->getWidgets();
    }

    /**
     * Returns the list of widgets names and options for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getUserConfig(User $user): array
    {
        $bookmark = $this->getBookmark($user);
        if ($bookmark !== null) {
            $config = $bookmark->getContent();
            if (\count($config) > 0) {
                return $config;
            }
        }

        $widgets = [];

        foreach ($this->getDefaultConfig() as $name) {
            $widgets[] = ['id' => $name, 'options' => []];
        }

        return $widgets;
    }

    /**
     * @param array<WidgetInterface> $widgets
     * @return array<WidgetInterface>
     */
    private function filterWidgets(array $widgets, User $user): array
    {
        $filteredWidgets = [];

        foreach ($this->getUserConfig($user) as $setting) {
            $id = $setting['id'];
            $options = $setting['options'];
            foreach ($widgets as $widget) {
                if ($widget->getId() === $id) {
                    $tmpWidget = clone $widget;
                    // protect from invalid old values
                    if (!\is_array($options)) {
                        break;
                    }
                    foreach ($options as $key => $value) {
                        if (!\is_scalar($value)) {
                            continue;
                        }
                        $tmpWidget->setOption($key, $value);
                    }
                    $filteredWidgets[] = $tmpWidget;
                    break;
                }
            }
        }

        return $filteredWidgets;
    }

    #[Route(path: '/', defaults: [], name: 'dashboard', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $available = $this->getAllAvailableWidgets($user);
        $widgets = $this->filterWidgets($available, $user);

        $page = new PageSetup('dashboard.title');
        $page->setHelp('dashboard.html');
        $page->setActionName('dashboard');
        $page->setActionPayload(['widgets' => $widgets, 'available' => $available]);

        $choices = [];

        // the list of widgets for the dropdown is mainly used for saving
        foreach ($available as $widget) {
            if ($widget->isInternal() || $widget->getTitle() === '') {
                continue;
            }
            $choices[$widget->getId()] = $widget->getId();
        }

        $form = $this->createFormBuilder(null, [])
            ->add('widgets', TextType::class, ['constraints' => new Json([
                    'message' => 'Invalid widget configuration',
                ])
            ])
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $error = null;
            $saveWidgets = [];
            if (!$form->isValid()) {
                $error = 'Invalid widget configuration';
            } else {
                $widgetsJson = $form->getData()['widgets'];
                $json = false;
                if (\is_string($widgetsJson)) {
                    $json = json_decode($widgetsJson, true);
                }
                if ($json === false) {
                    $error = 'Invalid widget configuration';
                } else {
                    try {
                        foreach ($json as $widget) {
                            if (!\is_array($widget) || !isset($widget['name']) || !isset($widget['options'])) {
                                $error = 'Invalid widget configuration';
                            } else {
                                $widgetId = $widget['name'];
                                $options = $widget['options'];
                                if (!\is_array($options)) {
                                    throw new \InvalidArgumentException('Widgets options must be an array');
                                }
                                foreach ($available as $tmpWidget) {
                                    if ($widgetId === $tmpWidget->getId()) {
                                        $saveWidgets[] = ['id' => $widgetId, 'options' => $options];
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $ex) {
                        $this->flashDeleteException($ex);
                    }
                }
            }

            if ($error !== null) {
                $this->flashError($error);
            } else {
                $this->saveBookmark($user, $saveWidgets);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'page_setup' => $page,
            'widgets' => $widgets,
            'available' => $available,
            'form' => $form,
        ]);
    }

    #[Route(path: '/reset/', defaults: [], name: 'dashboard_reset', methods: ['GET', 'POST'])]
    public function reset(): RedirectResponse
    {
        $bookmark = $this->getBookmark($this->getUser());
        if ($bookmark !== null) {
            $this->repository->deleteBookmark($bookmark);
        }

        return $this->redirectToRoute('dashboard');
    }

    #[Route(path: '/add-widget/{widget}', defaults: [], name: 'dashboard_add', methods: ['GET'])]
    public function add(string $widget): Response
    {
        $user = $this->getUser();
        $widgets = $this->getUserConfig($user);

        // prevent to add the same widget multiple times
        foreach ($widgets as $id => $setting) {
            if ($setting['id'] === $widget) {
                $this->flashError(\sprintf('Cannot add widget "%s" multiple times.', $widget));

                return $this->redirectToRoute('dashboard');
            }
        }

        $widgets[] = ['id' => $widget, 'options' => []];
        $this->saveBookmark($user, $widgets);

        return $this->redirectToRoute('dashboard');
    }

    private function saveBookmark(User $user, array $widgets): void
    {
        $bookmark = $this->getBookmark($user);
        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setType(self::BOOKMARK_TYPE);
            $bookmark->setName(self::BOOKMARK_NAME);
        }
        $bookmark->setContent($widgets);

        $this->repository->saveBookmark($bookmark);
    }
}
