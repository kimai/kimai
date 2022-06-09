/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiThemeInitializer: initialize theme functionality
 */

import { Tooltip } from 'bootstrap';
import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiThemeInitializer extends KimaiPlugin {

    init() {
        this.registerGlobalAjaxErrorHandler();
        this.registerGlobalAjaxSuccessHandler();

        // the tooltip do not use data-bs-toggle="tooltip" so they can be mixed with data-toggle="modal"
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new Tooltip(tooltipTriggerEl);
        });

        // activate all form plugins
        this.getContainer().getPlugin('form').activateForm('div.page-wrapper form', 'body');
        this.getContainer().getPlugin('form').activateForm('form.searchform', 'body');

        this.registerModalAutofocus('#modal_search');
        this.registerModalAutofocus('#remote_form_modal');
        this.registerOverlayListener('kimai.reloadContent', 'kimai.reloadedContent');
    }

    /**
     * Registers an event listener, that will is capabale of displaying and hiding overlays upon notification
     *
     * @param {string} eventNameShow
     * @param {string} eventNameHide
     */
    registerOverlayListener(eventNameShow, eventNameHide) {
        this.overlay = null;
        const self = this;

        document.addEventListener(eventNameShow, function(event) {
            // do not allow more than one loading screen at a time
            if (self.overlay !== null) {
                return;
            }

            // at which element we append the loading screen
            let container = 'body';
            if (event.detail !== undefined && event.detail !== null) {
                container = event.detail;
            }

            self.overlay = jQuery('<div class="overlay"><div class="fas fa-sync fa-spin"></div></div>');
            jQuery(container).append(self.overlay);
        });

        document.addEventListener(eventNameHide, function(event) {
            if (self.overlay !== null) {
                jQuery(self.overlay).remove();
                self.overlay = null;
            }
        });
    }

    /**
     * workaround for autofocus attribute, as the modal "steals" it
     *
     * @param {string} selector
     */
    registerModalAutofocus(selector) {
        let modal = jQuery(selector);
        if (modal.length === 0) {
            return;
        }

        modal.on('shown.bs.modal', function () {
            let form = modal.find('form');
            let formAutofocus = form.find('[autofocus]');
            if (formAutofocus.length < 1) {
                formAutofocus = form.find('input[type=text],textarea,select');
            }
            formAutofocus.filter(':not("[data-datetimepicker=on]")').filter(':visible:first').focus().delay(1000).focus();
        });
    }

    /**
     * redirect access denied / session timeouts to login page
     */
    registerGlobalAjaxErrorHandler() {
        const loginUrl = this.getConfiguration('login');
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
     * listen for a project specific header, that is only set after an entity was created
     */
    registerGlobalAjaxSuccessHandler() {
        jQuery(document).ajaxSuccess(function(event, jqxhr, settings, data) {
            if (jqxhr.status !== undefined && jqxhr.status === 201) {
                const successRedirect = jqxhr.getResponseHeader('x-modal-redirect');
                if (successRedirect !== null) {
                    window.location = successRedirect;
                }
            }
        });
    }
}
