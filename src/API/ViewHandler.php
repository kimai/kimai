<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Utils\Pagination;
use FOS\RestBundle\View\ConfigurableViewHandlerInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewHandler implements ConfigurableViewHandlerInterface
{
    public function __construct(private readonly ConfigurableViewHandlerInterface $baseViewHandler)
    {
    }

    /**
     * @param string[]|string $groups
     */
    public function setExclusionStrategyGroups($groups): void
    {
        $this->baseViewHandler->setExclusionStrategyGroups($groups);
    }

    public function setExclusionStrategyVersion(string $version): void
    {
        $this->baseViewHandler->setExclusionStrategyVersion($version);
    }

    public function setSerializeNullStrategy(bool $isEnabled): void
    {
        $this->baseViewHandler->setSerializeNullStrategy($isEnabled);
    }

    public function supports(string $format): bool
    {
        return $this->baseViewHandler->supports($format);
    }

    public function registerHandler(string $format, callable $callable): void
    {
        $this->baseViewHandler->registerHandler($format, $callable);
    }

    public function handle(View $view, ?Request $request = null): Response
    {
        $data = $view->getData();

        if ($data instanceof Pagination) {
            $results = (array) $data->getCurrentPageResults();
            $view->setData($results);

            $view->setHeader('X-Page', (string) $data->getCurrentPage());
            $view->setHeader('X-Total-Count', (string) $data->getNbResults());
            $view->setHeader('X-Total-Pages', (string) $data->getNbPages());
            $view->setHeader('X-Per-Page', (string) $data->getMaxPerPage());
        }

        return $this->baseViewHandler->handle($view, $request);
    }

    public function createRedirectResponse(View $view, string $location, string $format): Response
    {
        return $this->baseViewHandler->createRedirectResponse($view, $location, $format);
    }

    public function createResponse(View $view, Request $request, string $format): Response
    {
        return $this->baseViewHandler->createResponse($view, $request, $format);
    }
}
