/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEvent: helper to trigger events
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiEvent extends KimaiPlugin {

    getId()
    {
        return 'event';
    }

    /**
     * @param {string} name
     * @param {string|array|object|null} details
     */
    trigger(name, details = null)
    {
        if (name === '') {
            return;
        }

        for (const event of name.split(' ')) {
            let triggerEvent = new Event(event);
            if (details !== null) {
                triggerEvent = new CustomEvent(event, {detail: details});
            }
            document.dispatchEvent(triggerEvent);
        }
    }
}
