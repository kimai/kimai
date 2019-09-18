<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Entity\MetaTableTypeInterface;
use App\Entity\Timesheet;
use App\Event\ActivityMetaQueryEvent;
use App\Event\CustomerMetaQueryEvent;
use App\Event\MetaQueryEventInterface;
use App\Event\ProjectMetaQueryEvent;
use App\Event\TimesheetMetaQueryEvent;
use App\Export\RendererInterface;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class HtmlRenderer implements RendererInterface
{
    use RendererTrait;

    /**
     * @var Environment
     */
    protected $twig;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(Environment $twig, EventDispatcherInterface $dispatcher)
    {
        $this->twig = $twig;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param MetaQueryEventInterface $event
     * @return MetaTableTypeInterface[]
     */
    protected function findMetaColumns(MetaQueryEventInterface $event): array
    {
        $this->dispatcher->dispatch($event);

        $columns = [];

        foreach ($event->getFields() as $field) {
            if (!$field->isVisible()) {
                continue;
            }
            $columns[] = $field;
        }

        return $columns;
    }

    /**
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        $timesheetMetaFields = $this->findMetaColumns(new TimesheetMetaQueryEvent($query, TimesheetMetaQueryEvent::EXPORT));
        $customerMetaFields = $this->findMetaColumns(new CustomerMetaQueryEvent($query, CustomerMetaQueryEvent::EXPORT));
        $projectMetaFields = $this->findMetaColumns(new ProjectMetaQueryEvent($query, ProjectMetaQueryEvent::EXPORT));
        $activityMetaFields = $this->findMetaColumns(new ActivityMetaQueryEvent($query, ActivityMetaQueryEvent::EXPORT));

        $content = $this->twig->render('export/renderer/default.html.twig', [
            'entries' => $timesheets,
            'query' => $query,
            'summaries' => $this->calculateSummary($timesheets),
            'timesheetMetaFields' => $timesheetMetaFields,
            'customerMetaFields' => $customerMetaFields,
            'projectMetaFields' => $projectMetaFields,
            'activityMetaFields' => $activityMetaFields,
        ]);

        $response = new Response();
        $response->setContent($content);

        return $response;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'html';
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return 'print';
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'print';
    }
}
