<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Activity;
use App\Webhook\Attribute\AsWebhook;

#[AsWebhook(name: 'activity.deleted', description: 'Triggered right before an activity will be deleted', payload: 'object.getActivity()')]
final class ActivityDeleteEvent extends AbstractActivityEvent
{
    public function __construct(Activity $activity, private readonly ?Activity $replacementActivity = null)
    {
        parent::__construct($activity);
    }

    public function getReplacementActivity(): ?Activity
    {
        return $this->replacementActivity;
    }
}
