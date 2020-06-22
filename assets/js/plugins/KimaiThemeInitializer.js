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
        this.registerGlobalAjaxErrorHandler();
        this.registerAutomaticAlertRemove('div.alert-success', 5000);
        // activate the dropdown functionality
        jQuery('.dropdown-toggle').dropdown();
        // activate the tooltip functionality
        jQuery('[data-toggle="tooltip"]').tooltip();
        // activate all form plugins
        this.getContainer().getPlugin('form').activateForm('.content-wrapper form', 'body');
    }

    /**
     * redirect access denied / session timeouts to login page
     */
    registerGlobalAjaxErrorHandler() {
        const loginUrl = this.getContainer().getConfiguration().get('login');
        const alert = this.getContainer().getPlugin('alert');
        const translation = this.getContainer().getTranslation().get('login.required');
        jQuery(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            if (jqxhr.status !== undefined && jqxhr.status === 403) {
                const loginRequired = jqxhr.getResponseHeader('login-required');
                if (loginRequired !== null) {
                    alert.question(translation, function (result) {
                        if (result === true) {
                            window.location.replace(loginUrl);
                        }
                    });
                }
            }
        });
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
