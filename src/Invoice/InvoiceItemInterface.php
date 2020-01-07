<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Activity;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\User;

/**
 * @deprecated will be removed with 2.0 - use ExportItemInterface instead
 */
interface InvoiceItemInterface
{
    public function getActivity(): ?Activity;

    public function getProject(): ?Project;

    public function getFixedRate(): ?float;

    public function getHourlyRate(): ?float;

    public function getRate(): float;

    public function getUser(): ?User;

    public function getBegin(): ?\DateTime;

    public function getEnd(): ?\DateTime;

    public function getDuration(): ?int;

    public function getDescription(): ?string;

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getVisibleMetaFields(): array;
}
