<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\Pagination;
use App\Utils\PaginationView;
use Pagerfanta\View\ViewInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PaginationExtension extends AbstractExtension
{
    private ?ViewInterface $view = null;

    public function __construct(private UrlGeneratorInterface $router)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pagination', [$this, 'renderPagination'], ['is_safe' => ['html']]),
        ];
    }

    private function getView(): ViewInterface
    {
        if (null === $this->view) {
            $this->view = new PaginationView();
        }

        return $this->view;
    }

    /**
     * @param Pagination<string> $pager
     * @param array<string, mixed> $options
     */
    public function renderPagination(Pagination $pager, array $options = []): string
    {
        $routeGenerator = $this->createRouteGenerator($options);

        return $this->getView()->render($pager, $routeGenerator, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createRouteGenerator(array $options = []): \Closure
    {
        /** @var array{routeName: string, routeParams: array<string, mixed>, pageParameter: string} $options */
        $options = array_replace([
            'routeName' => null,
            'routeParams' => [],
            'pageParameter' => '[page]',
        ], $options);

        $router = $this->router;

        if (null === $options['routeName']) {
            throw new \Exception('Pagination is missing the "routeName" option');
        }

        $routeName = $options['routeName'];
        $routeParams = $options['routeParams'];
        $pagePropertyPath = new PropertyPath($options['pageParameter']);

        return function ($page) use ($router, $routeName, $routeParams, $pagePropertyPath) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $propertyAccessor->setValue($routeParams, $pagePropertyPath, $page);

            return $router->generate($routeName, $routeParams);
        };
    }
}
