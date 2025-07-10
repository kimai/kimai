<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'customer.deleted', description: 'Triggered right before a customer will be deleted', payload: 'object.getCustomer()')]
final class CustomerDeleteEvent extends AbstractCustomerEvent
{
}
