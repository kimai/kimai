<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;

/**
 * Find items (eg timesheets) for creating a new invoice.
 */
class InvoiceQuery extends TimesheetQuery
{
    /**
     * @var InvoiceTemplate
     */
    private $template;
    /**
     * @var bool
     */
    private $markAsExported = true;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults([
            'order' => InvoiceQuery::ORDER_ASC,
            'exported' => InvoiceQuery::STATE_NOT_EXPORTED,
            'state' => self::STATE_STOPPED,
            'billable' => true,
            'markAsExported' => true,
        ]);
    }

    public function getTemplate(): ?InvoiceTemplate
    {
        return $this->template;
    }

    public function setTemplate(InvoiceTemplate $template): InvoiceQuery
    {
        $this->template = $template;

        return $this;
    }

    public function isMarkAsExported(): bool
    {
        return $this->markAsExported;
    }

    public function setMarkAsExported(bool $markAsExported): InvoiceQuery
    {
        $this->markAsExported = $markAsExported;

        return $this;
    }

    /**
     * This method ONLY exists, because many templates out there access {{ model.query.customer }} directly.
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        $customers = $this->getCustomers();
        if (\count($customers) === 1) {
            $customer = $customers[0];
            if ($customer instanceof Customer) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * This method ONLY exists, because many templates out there access {{ model.query.project }} directly.
     *
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        $projects = $this->getProjects();
        if (\count($projects) === 1) {
            $project = $projects[0];
            if ($project instanceof Project) {
                return $project;
            }
        }

        return null;
    }

    /**
     * This method ONLY exists, because many templates out there access {{ model.query.activity }} directly.
     *
     * @return Activity|null
     */
    public function getActivity(): ?Activity
    {
        $activities = $this->getActivities();
        if (\count($activities) === 1) {
            $activity = $activities[0];
            if ($activity instanceof Activity) {
                return $activity;
            }
        }

        return null;
    }
}
