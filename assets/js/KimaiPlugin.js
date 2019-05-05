/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiPlugin: base class for all plugins
 */

import KimaiCore from "./KimaiCore";

export default class KimaiPlugin {

    init() {
        // overwrite this method to initialize your plugin
        // it's called AFTER setCore() was called and AFTER DOMContentLoaded was fired
    }

    getId() {
        throw new Error('Plugins must overwrite the getId() function');
    }

    setCore(core) {
        if (!(core instanceof KimaiCore)) {
            throw new Error('Plugin was given an invalid KimaiCore');
        }
        this._core = core;
    }

    getCore() {
        return this._core;
    }

}
