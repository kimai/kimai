/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiThemeInitializer: initialize theme functionality
 */

import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiThemeInitializer extends KimaiPlugin {

    getId() {
        return 'theme-initializer';
    }

    init() {
        this.registerAutomaticAlertRemove('div.alert-success', 5000);
    }

    /**
     * auto hide success messages, as they are just meant as user feedback and not as a permanent information
     *
     * @param string selector
     * @param number interval
     */
    registerAutomaticAlertRemove(selector, interval) {
        this._alertRemoveHandler = setInterval(
            function() {
                jQuery(selector).alert('close');
            },
            interval
        );
    }

    unregisterAutomaticAlertRemove() {
        clearInterval(this._alertRemoveHandler);
    }

}
