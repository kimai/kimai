<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Entity\Timesheet;
use App\Event\TimesheetMetaDefinitionEvent;
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
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        $publicMetaFields = [];
        foreach ($timesheets as $timesheet) {
            $event = new TimesheetMetaDefinitionEvent($timesheet);
            $this->dispatcher->dispatch(TimesheetMetaDefinitionEvent::class, $event);

            foreach ($timesheet->getMetaFields() as $metaField) {
                if ($metaField->isPublicVisible()) {
                    $publicMetaFields[] = $metaField->getName();
                }
            }
        }

        $content = $this->twig->render('export/renderer/default.html.twig', [
            'entries' => $timesheets,
            'query' => $query,
            'metaFields' => array_unique($publicMetaFields),
            'summaries' => $this->calculateSummary($timesheets),
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
