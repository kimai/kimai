<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Repository\Query\TimesheetQuery;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated since 1.13 - will be removed with 2.0
 */
class DeprecatedExportRenderer implements ExportRenderer, ExportRendererInterface
{
    /**
     * @var ExportRendererInterface
     */
    private $renderer;

    public function __construct(ExportRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function create(Export $export): Response
    {
        @trigger_error(
            sprintf('ExportRendererInterface::render() in %s is deprecated and will be removed with 2.0', \get_class($this->renderer)),
            E_USER_DEPRECATED
        );

        return $this->renderer->render($export->getItems(), $export->getQuery());
    }

    public function getId(): string
    {
        return $this->renderer->getId();
    }

    public function getIcon(): string
    {
        return $this->renderer->getIcon();
    }

    public function getTitle(): string
    {
        return $this->renderer->getTitle();
    }

    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        return $this->renderer->render($exportItems, $query);
    }
}
