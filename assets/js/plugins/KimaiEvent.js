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

    trigger(name) {
        if (name === null) {
            return;
        }

        document.dispatchEvent(new Event(name));
    }

}
