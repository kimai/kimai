<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrap3View;
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
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->view = new TwitterBootstrap3View();
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('pagerfanta', [$this, 'renderPagerfanta'], ['is_safe' => ['html']]),
            new TwigFunction('pagination', [$this, 'renderPagination'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @deprecated since 1.8
     */
    public function renderPagerfanta(Pagerfanta $pagerfanta, $viewName = null, array $options = [])
    {
        @trigger_error('Twig function pagerfanta() is deprecated and will be removed with 2.0, use pagination() instead', E_USER_DEPRECATED);

        if (\is_array($viewName)) {
            $options = $viewName;
        }

        return $this->renderPagination($pagerfanta, $options);
    }

    public function renderPagination(Pagerfanta $pagerfanta, array $options = [])
    {
        $routeGenerator = $this->createRouteGenerator($options);

        $options['proximity'] = 1;
        //$options['prev_message'] = '←';
        //$options['next_message'] = '→';
        $options['prev_message'] = '<i class="fas fa-chevron-left"></i>';
        $options['next_message'] = '<i class="fas fa-chevron-right"></i>';

        return $this->view->render($pagerfanta, $routeGenerator, $options);
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
