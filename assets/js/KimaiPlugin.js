/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiPlugin: base class for all plugins
 */

import KimaiContainer from "./KimaiContainer";

export default class KimaiPlugin {

    /**
     * Overwrite this method to initialize your plugin.
     *
     * It is called AFTER setContainer() and AFTER DOMContentLoaded was fired.
     * You don't have access to the container before this method!
     */
    init() {
    }

    /**
     * If you return an ID, you indicate that your plugin can be used by other plugins.
     *
     * @returns {string|null}
     */
    getId() {
        return null;
    }

    /**
     * @param {KimaiContainer} core
     */
    setContainer(core) {
        if (!(core instanceof KimaiContainer)) {
            throw new Error('Plugin was given an invalid KimaiContainer');
        }
        this._core = core;
    }

    /**
     * This function returns null, if xou call it BEFORE init().
     *
     * @returns {KimaiContainer}
     */
    getContainer() {
        return this._core;
    }

}
