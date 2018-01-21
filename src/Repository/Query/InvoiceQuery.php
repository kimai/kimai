<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\InvoiceTemplate;

/**
 * Can be used for invoice queries.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class InvoiceQuery extends TimesheetQuery
{

    /**
     * @var InvoiceTemplate
     */
    protected $template;

    /**
     * @var InvoiceTemplate[]
     */
    protected $templates = [];

    /**
     * @return InvoiceTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param InvoiceTemplate $template
     * @return InvoiceQuery
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return InvoiceTemplate[]
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @param InvoiceTemplate[] $templates
     * @return InvoiceQuery
     */
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
        return $this;
    }
}
