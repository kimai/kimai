<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Activity\ActivityStatisticService;
use App\Entity\ExportableItem;
use App\Entity\MetaTableTypeInterface;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\ExportRendererInterface;
use App\Project\ProjectStatisticService;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\TimesheetQuery;
use App\Twig\SecurityPolicy\StrictPolicy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * TODO 3.0 remove default values from constructor parameters and make class final
 * @final
 */
#[Exclude]
class HtmlRenderer implements ExportRendererInterface
{
    use RendererTrait;

    public function __construct(
        protected readonly Environment $twig,
        protected readonly EventDispatcherInterface $dispatcher,
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly ActivityStatisticService $activityStatisticService,
        private string $id = 'html', // deprecated default parameter - TODO 3.0
        private readonly string $title = 'print', // deprecated default parameter - TODO 3.0
        private string $template = 'export/print.html.twig', // deprecated default parameter - TODO 3.0
    ) {
    }

    public function isInternal(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return 'html';
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    protected function findMetaColumns(MetaDisplayEventInterface $event): array
    {
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    protected function getOptions(TimesheetQuery $query): array
    {
        $decimal = false;
        if (null !== $query->getCurrentUser()) {
            $decimal = $query->getCurrentUser()->isExportDecimal();
        } elseif (null !== $query->getUser()) {
            $decimal = $query->getUser()->isExportDecimal();
        }

        return ['decimal' => $decimal];
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        $timesheetMetaFields = $this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT));
        $customerMetaFields = $this->findMetaColumns(new CustomerMetaDisplayEvent($query->copyTo(new CustomerQuery()), CustomerMetaDisplayEvent::EXPORT));
        $projectMetaFields = $this->findMetaColumns(new ProjectMetaDisplayEvent($query->copyTo(new ProjectQuery()), ProjectMetaDisplayEvent::EXPORT));
        $activityMetaFields = $this->findMetaColumns(new ActivityMetaDisplayEvent($query->copyTo(new ActivityQuery()), ActivityMetaDisplayEvent::EXPORT));

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->dispatcher->dispatch($event);
        $userPreferences = $event->getPreferences();

        $summary = $this->calculateSummary($exportItems);

        // enable basic security measures
        if (!$this->twig->hasExtension(SandboxExtension::class)) {
            $this->twig->addExtension(new SandboxExtension(new StrictPolicy()));
        }

        $sandbox = $this->twig->getExtension(SandboxExtension::class);
        $sandbox->enableSandbox();

        $content = $this->twig->render($this->getTemplate(), array_merge([
            'entries' => $exportItems,
            'query' => $query,
            'summaries' => $summary,
            'budgets' => $this->calculateProjectBudget($exportItems, $query, $this->projectStatisticService),
            'activity_budgets' => $this->calculateActivityBudget($exportItems, $query, $this->activityStatisticService),
            'timesheetMetaFields' => $timesheetMetaFields,
            'customerMetaFields' => $customerMetaFields,
            'projectMetaFields' => $projectMetaFields,
            'activityMetaFields' => $activityMetaFields,
            'userPreferences' => $userPreferences,
        ], $this->getOptions($query)));

        // allows to run in development mode, otherwise toolbar would be blocked
        $sandbox->disableSandbox();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $response->setContent($content);

        return $response;
    }

    protected function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @deprecated since 2.40.0
     */
    public function setTemplate(string $filename): void
    {
        $this->template = '@export/' . $filename;
    }

    /**
     * @deprecated since 2.40.0
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
