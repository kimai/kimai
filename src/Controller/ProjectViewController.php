<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Project;
use App\Event\ProjectMetaDisplayEvent;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/project/project_view")
 * @Security("is_granted('details_project')")
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
     * @return array
     */
    protected function findMetaColumns(ProjectQuery $query): array
    {
        $event = new ProjectMetaDisplayEvent($query, ProjectMetaDisplayEvent::PROJECT);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }
}
