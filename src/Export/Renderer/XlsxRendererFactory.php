<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\Base\XlsxRenderer;
use App\Export\ColumnConverter;
use App\Export\DefaultTemplate;
use App\Export\TemplateInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class XlsxRendererFactory
{
    public function __construct(
        private readonly ColumnConverter $converter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function createDefault(): XlsxRenderer
    {
        $template = new DefaultTemplate($this->eventDispatcher, 'xlsx', 'en', 'xlsx');

        return new XlsxRenderer($this->converter, $this->translator, $template);
    }

    public function create(TemplateInterface $template): XlsxRenderer
    {
        $renderer = new XlsxRenderer($this->converter, $this->translator, $template);
        $renderer->setInternal(true);

        return $renderer;
    }
}
