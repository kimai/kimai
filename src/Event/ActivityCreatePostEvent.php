<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'activity.created', description: 'Triggered after a new activity was created', payload: 'object.getActivity()')]
final class ActivityCreatePostEvent extends AbstractActivityEvent
{
}
