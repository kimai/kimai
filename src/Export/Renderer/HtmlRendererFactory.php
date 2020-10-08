<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Repository\ProjectRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

final class HtmlRendererFactory
{
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(Environment $twig, EventDispatcherInterface $dispatcher, ProjectRepository $projectRepository)
    {
        $this->twig = $twig;
        $this->dispatcher = $dispatcher;
        $this->projectRepository = $projectRepository;
    }

    public function create(string $id, string $template): HtmlRenderer
    {
        $renderer = new HtmlRenderer($this->twig, $this->dispatcher, $this->projectRepository);
        $renderer->setId($id);
        $renderer->setTemplate($template);

        return $renderer;
    }
}
