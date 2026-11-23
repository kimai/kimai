<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\Base\CsvRenderer;
use App\Export\ColumnConverter;
use App\Export\DefaultTemplate;
use App\Export\TemplateInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CsvRendererFactory
{
    public function __construct(
        private readonly ColumnConverter $converter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function create(TemplateInterface $template): CsvRenderer
    {
        $renderer = new CsvRenderer($this->converter, $this->translator, $template);
        $renderer->setInternal(true);

        return $renderer;
    }

    public function createDefault(): CsvRenderer
    {
        $template = new DefaultTemplate($this->eventDispatcher, 'csv', 'en', 'csv');

        return new CsvRenderer($this->converter, $this->translator, $template);
    }
}
