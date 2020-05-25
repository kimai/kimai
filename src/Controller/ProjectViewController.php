<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Project;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use App\Event\ProjectMetaDisplayEvent;

/**
 * @Route(path="/project_view")
 */
final class ProjectViewController extends AbstractController
{
    /**
     * @var ProjectRepository
     */
    private $repository;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(ProjectRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route(path="", defaults={}, name="project_view", methods={"GET"})
     */
    public function indexAction(Request $request): Response
    {
        $query = new ProjectQuery();

        $entries = $this->repository->getProjectView();

        return $this->render('project_view/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'metaColumns' => $this->findMetaColumns($query)
        ]);
    }

    /**
     * @param ProjectQuery $query
     * @return MetaTableTypeInterface[]
     */
    protected function findMetaColumns(ProjectQuery $query): array
    {
        $event = new ProjectMetaDisplayEvent($query, ProjectMetaDisplayEvent::PROJECT);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }
}
