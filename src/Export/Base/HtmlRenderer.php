<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\MetaTableTypeInterface;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\MetaDisplayEventInterface;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\Export;
use App\Repository\ProjectRepository;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\TimesheetQuery;
use App\Twig\EnvironmentTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class HtmlRenderer
{
    use RendererTrait;
    use EnvironmentTrait;

    /**
     * @var Environment
     */
    protected $twig;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;
    /**
     * @var string
     */
    private $id = 'html';
    /**
     * @var string
     */
    private $template = 'default.html.twig';

    public function __construct(Environment $twig, EventDispatcherInterface $dispatcher, ProjectRepository $projectRepository)
    {
        $this->twig = $twig;
        $this->dispatcher = $dispatcher;
        $this->projectRepository = $projectRepository;
    }

    /**
     * @param MetaDisplayEventInterface $event
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
            $decimal = (bool) $query->getCurrentUser()->getPreferenceValue('timesheet.export_decimal', $decimal);
        } elseif (null !== $query->getUser()) {
            $decimal = (bool) $query->getUser()->getPreferenceValue('timesheet.export_decimal', $decimal);
        }

        return ['decimal' => $decimal];
    }

    /**
     * @param Export $export
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function create(Export $export): Response
    {
        $timesheets = $export->getItems();
        $query = $export->getQuery();

        $previousLocale = null;
        if ($export->getLanguage() !== '__XX__') {
            $previousLocale = $this->changeLocale($this->twig, $export->getLanguage());
        }

        /** @var CustomerQuery $customerQuery */
        $customerQuery = $query->copyTo(new CustomerQuery());

        $timesheetMetaFields = $this->findMetaColumns(new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT));
        $customerMetaFields = $this->findMetaColumns(new CustomerMetaDisplayEvent($customerQuery, CustomerMetaDisplayEvent::EXPORT));
        $projectMetaFields = $this->findMetaColumns(new ProjectMetaDisplayEvent($query, ProjectMetaDisplayEvent::EXPORT));
        $activityMetaFields = $this->findMetaColumns(new ActivityMetaDisplayEvent($query, ActivityMetaDisplayEvent::EXPORT));

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::EXPORT);
        $this->dispatcher->dispatch($event);
        $userPreferences = $event->getPreferences();

        $summary = $this->calculateSummary($timesheets);

        $content = $this->twig->render($this->getTemplate(), array_merge([
            'entries' => $timesheets,
            'query' => $query,
            'summaries' => $summary,
            'budgets' => $this->calculateProjectBudget($timesheets, $query, $this->projectRepository),
            // @deprecated since 1.13, will be removed with 2.0
            'metaColumns' => $timesheetMetaFields,
            'timesheetMetaFields' => $timesheetMetaFields,
            'customerMetaFields' => $customerMetaFields,
            'projectMetaFields' => $projectMetaFields,
            'activityMetaFields' => $activityMetaFields,
            'userPreferences' => $userPreferences,
        ], $this->getOptions($query)));

        if ($previousLocale !== null) {
            $this->changeLocale($this->twig, $previousLocale);
        }

        $response = new Response();
        $response->setContent($content);

        return $response;
    }

    /**
     * @deprecated since 1.13 - will be removed with 2.0
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        return $this->create(
            new Export($timesheets, $query, $query->getUser() ?? $query->getCurrentUser(), '__XX__')
        );
    }

    protected function getTemplate(): string
    {
        return '@export/' . $this->template;
    }

    public function setTemplate(string $filename): HtmlRenderer
    {
        $this->template = $filename;

        return $this;
    }

    public function setId(string $id): HtmlRenderer
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIcon(): string
    {
        return 'print';
    }

    public function getTitle(): string
    {
        return 'print';
    }
}
