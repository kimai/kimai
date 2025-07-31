<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\Base\CsvRenderer;
use App\Export\Base\SpreadsheetRenderer;
use App\Export\TemplateInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CsvRendererFactory
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly Security $voter,
        private readonly TranslatorInterface $translator,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function create(TemplateInterface $template): CsvRenderer
    {
        $renderer = new SpreadsheetRenderer($this->dispatcher, $this->voter, $this->logger);
        $renderer->setTemplate($template);

        $renderer = new CsvRenderer($renderer, $this->translator);
        $renderer->setId($template->getId());
        $renderer->setTitle($template->getTitle());
        $renderer->setLocale($template->getLocale());

        return $renderer;
    }
}
