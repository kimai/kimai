<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\ContractMonthSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\ContractMonthSubscriber
 */
class ContractMonthSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName()
    {
        $this->assertGetSubscribedEvent(ContractMonthSubscriber::class, 'contract_month');
    }
}
