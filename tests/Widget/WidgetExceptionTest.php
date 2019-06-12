<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget;

use App\Repository\WidgetRepository;
use App\Widget\Renderer\SimpleWidgetRenderer;
use App\Widget\Type\Counter;
use App\Widget\Type\More;
use App\Widget\WidgetException;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Widget\WidgetException
 */
class WidgetExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $ex = new WidgetException();
        $this->assertInstanceOf(\Exception::class, $ex);
    }
}
