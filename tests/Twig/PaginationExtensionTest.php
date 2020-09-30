<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\PaginationExtension;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\PaginationExtension
 */
class PaginationExtensionTest extends TestCase
{
    private function getUrlGenerator()
    {
        $urlGenerator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $urlGenerator
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnCallback(function ($name, $parameters = []) {
                $params = [];
                foreach ($parameters as $k => $v) {
                    $params[] = $k . '=' . $v;
                }

                return (string) $name . '?' . implode('&', $params);
            }))
        ;

        return $urlGenerator;
    }

    protected function getSut(): PaginationExtension
    {
        return new PaginationExtension($this->getUrlGenerator());
    }

    public function testGetFunctions()
    {
        $functions = ['pagerfanta', 'pagination'];
        $sut = $this->getSut();
        $twigFunctions = $sut->getFunctions();
        self::assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            self::assertInstanceOf(TwigFunction::class, $filter);
            self::assertEquals($functions[$i++], $filter->getName());
        }
    }

    /**
     * @group legacy
     */
    public function testDeprecatedRenderPagerfanta()
    {
        $sut = $this->getSut();

        $values = array_fill(0, 151, 'blub');
        $pagerfanta = new Pagerfanta(new ArrayAdapter($values));
        $result = $sut->renderPagerfanta($pagerfanta, 'twitter_bootstrap3_translated', [
            'css_container_class' => 'pagination pagination-sm inline',
            'routeName' => 'project_activities',
            'routeParams' => ['id' => 137]
        ]);
        $this->assertPaginationHtml($result);
    }

    /**
     * @group legacy
     */
    public function testDeprecatedRenderPagerfantaWithoutTemplateName()
    {
        $sut = $this->getSut();

        $values = array_fill(0, 151, 'blub');
        $pagerfanta = new Pagerfanta(new ArrayAdapter($values));
        $result = $sut->renderPagerfanta($pagerfanta, [
            'css_container_class' => 'pagination pagination-sm inline',
            'routeName' => 'project_activities',
            'routeParams' => ['id' => 137]
        ]);
        $this->assertPaginationHtml($result);
    }

    protected function assertPaginationHtml($result)
    {
        $expected =
            '<ul class="pagination pagination-sm inline">' .
            '<li class="prev disabled"><span><i class="fas fa-chevron-left"></i></span></li>' .
            '<li class="active"><span>1 <span class="sr-only">(current)</span></span></li>' .
            '<li><a href="project_activities?id=137&page=2">2</a></li>' .
            '<li><a href="project_activities?id=137&page=3">3</a></li>' .
            '<li class="disabled"><span>&hellip;</span></li>' .
            '<li><a href="project_activities?id=137&page=16">16</a></li>' .
            '<li class="next"><a href="project_activities?id=137&page=2" rel="next"><i class="fas fa-chevron-right"></i></a></li></ul>';

        self::assertEquals($expected, $result);
    }

    public function testRenderPagination()
    {
        $sut = $this->getSut();

        $values = array_fill(0, 151, 'blub');
        $pagerfanta = new Pagerfanta(new ArrayAdapter($values));
        $result = $sut->renderPagination($pagerfanta, [
            'css_container_class' => 'pagination pagination-sm inline',
            'routeName' => 'project_activities',
            'routeParams' => ['id' => 137]
        ]);
        $this->assertPaginationHtml($result);
    }

    public function testRenderPaginationWithoutRouteName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pagination is missing the "routeName" option');

        $sut = $this->getSut();

        $values = array_fill(0, 151, 'blub');
        $pagerfanta = new Pagerfanta(new ArrayAdapter($values));
        $result = $sut->renderPagination($pagerfanta, [
            'css_container_class' => 'pagination pagination-sm inline',
            'routeParams' => ['id' => 137]
        ]);
        $this->assertPaginationHtml($result);
    }
}
