<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\API\ViewHandler;
use App\Utils\Pagination;
use FOS\RestBundle\View\ConfigurableViewHandlerInterface;
use FOS\RestBundle\View\View;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ViewHandler::class)]
class ViewHandlerTest extends TestCase
{
    public function testValues(): void
    {
        $base = $this->createMock(ConfigurableViewHandlerInterface::class);
        $base->expects($this->once())->method('supports')->willReturn(true);
        $base->expects($this->exactly(2))->method('setExclusionStrategyGroups');
        $base->expects($this->once())->method('setExclusionStrategyVersion');
        $base->expects($this->once())->method('setSerializeNullStrategy');
        $base->expects($this->once())->method('registerHandler');
        $base->expects($this->once())->method('createRedirectResponse')->willReturn(new Response());
        $base->expects($this->once())->method('createResponse')->willReturn(new Response());
        $base->expects($this->once())->method('handle')->willReturn(new Response());

        $sut = new ViewHandler($base);
        self::assertTrue($sut->supports('asdf'));
        $sut->setExclusionStrategyGroups(['bar', 'test']);
        $sut->setExclusionStrategyGroups('foo');
        $sut->setExclusionStrategyVersion('1.0');
        $sut->setSerializeNullStrategy(true);

        $sut->registerHandler('bla', function () {});
        $response = $sut->createRedirectResponse(new View('bar123'), 'https://www.example.com', 'json');
        self::assertInstanceOf(Response::class, $response);
        $response = $sut->createResponse(new View('bar123'), new Request(), 'json');
        self::assertInstanceOf(Response::class, $response);

        $results = ['foo' => 'bar', 'hello' => 'world'];

        $pagination = new Pagination(new ArrayAdapter($results));
        $view = new View($pagination);
        $headers = $view->getHeaders();

        self::assertSame($pagination, $view->getData());
        self::assertArrayNotHasKey('x-page', $headers);
        self::assertArrayNotHasKey('x-total-count', $headers);
        self::assertArrayNotHasKey('x-total-pages', $headers);
        self::assertArrayNotHasKey('x-per-page', $headers);

        $response = $sut->handle($view, new Request());
        self::assertInstanceOf(Response::class, $response);

        $headers = $view->getHeaders();
        self::assertSame($results, $view->getData());
        self::assertArrayHasKey('x-page', $headers);
        self::assertArrayHasKey('x-total-count', $headers);
        self::assertArrayHasKey('x-total-pages', $headers);
        self::assertArrayHasKey('x-per-page', $headers);
    }
}
