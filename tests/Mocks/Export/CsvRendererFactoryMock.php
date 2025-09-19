<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Export;

use App\Export\ColumnConverter;
use App\Export\Renderer\CsvRendererFactory;
use App\Tests\Mocks\AbstractMockFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvRendererFactoryMock extends AbstractMockFactory
{
    public function create(): CsvRendererFactory
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $converter = new ColumnConverter(
            $dispatcher,
            $this->createMock(Security::class),
            $this->createMock(LoggerInterface::class),
        );

        return new CsvRendererFactory(
            $converter,
            $dispatcher,
            $this->createMock(TranslatorInterface::class),
        );
    }
}
