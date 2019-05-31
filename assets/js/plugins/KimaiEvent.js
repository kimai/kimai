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

    getId() {
        return 'event';
    }

    trigger(name, details) {
        if (name === null || name === undefined) {
            return;
        }

        for(let event of name.split(' ')) {
            let triggerEvent = new Event(event);
            if (details !== undefined) {
                triggerEvent = new CustomEvent(name, {detail: details});
            }
            document.dispatchEvent(triggerEvent);
        }
    }
}
