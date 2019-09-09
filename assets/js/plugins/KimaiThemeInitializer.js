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

    init() {
        this.registerAutomaticAlertRemove('div.alert-success', 5000);

        // activate the dropdown functionality
        jQuery('.dropdown-toggle').dropdown();
        // activate the tooltip functionality
        jQuery('[data-toggle="tooltip"]').tooltip();
        // activate all form plugins
        this.getContainer().getPlugin('form').activateForm('.content-wrapper form', 'body');
    }

    /**
     * auto hide success messages, as they are just meant as user feedback and not as a permanent information
     *
     * @param {string} selector
     * @param {integer} interval
     */
    registerAutomaticAlertRemove(selector, interval) {
        const self = this;
        this._alertRemoveHandler = setInterval(
            function() {
                self.hideAlert(selector);
            },
            interval
        );
    }

    unregisterAutomaticAlertRemove() {
        clearInterval(this._alertRemoveHandler);
    }

    /**
     * @param {string} selector
     */
    hideAlert(selector) {
        jQuery(selector).alert('close');
    }

}
