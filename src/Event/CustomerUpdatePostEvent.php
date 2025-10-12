<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'customer.updated', description: 'Triggered after a customer was updated', payload: 'object.getCustomer()')]
final class CustomerUpdatePostEvent extends AbstractCustomerEvent
{
}
