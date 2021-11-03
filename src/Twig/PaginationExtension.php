<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\PaginationView;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\ViewInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaginationExtension extends AbstractExtension
{
    /**
     * @var ViewInterface
     */
    private $view;
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
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

    public function renderPagination(Pagerfanta $pagerfanta, array $options = [])
    {
        $routeGenerator = $this->createRouteGenerator($options);

        return $this->getView()->render($pagerfanta, $routeGenerator, $options);
    }

    private function createRouteGenerator(array $options = [])
    {
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
