/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiThemeInitializer: initialize theme functionality
 */

import { Tooltip, Offcanvas } from 'bootstrap';
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiThemeInitializer extends KimaiPlugin {

    init()
    {
        // the tooltip do not use data-bs-toggle="tooltip" so they can be mixed with data-toggle="modal"
        [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]')).map(function (tooltipTriggerEl) {
            return new Tooltip(tooltipTriggerEl);
        });

        // support for offcanvas elements
        const offcanvasElementList = document.querySelectorAll('.offcanvas');
        [...offcanvasElementList].map(offcanvasEl => new Offcanvas(offcanvasEl));

        // activate all form plugins
        /** @type {KimaiForm} FORMS */
        const FORMS = this.getContainer().getPlugin('form');
        FORMS.activateForm('div.page-wrapper form');

        this._registerModalAutofocus('#remote_form_modal');

        this.overlay = null;

        // register a global event listener, which displays an overlays upon notification
        document.addEventListener('kimai.reloadContent', (event) => {
            // do not allow more than one loading screen at a time
            if (this.overlay !== null) {
                return;
            }

            // at which element we append the loading screen
            let container = 'body';
            if (event.detail !== undefined && event.detail !== null) {
                container = event.detail;
            }

            const temp = document.createElement('div');
            temp.innerHTML = '<div class="overlay"><div class="fas fa-sync fa-spin"></div></div>';
            this.overlay = temp.firstElementChild;
            document.querySelector(container).append(this.overlay);
        });

        // register a global event listener, which hides an overlay upon notification
        document.addEventListener('kimai.reloadedContent', () => {
            if (this.overlay !== null) {
                this.overlay.remove();
                this.overlay = null;
            }
        });
    }

    /**
     * Helps to set the autofocus on modals.
     *
     * @param {string} selector
     */
    _registerModalAutofocus(selector) {
        // on mobile you do not want to trigger the virtual keyboard upon modal open
        if (this.isMobile()) {
            return;
        }

        const modal = document.querySelector(selector);
        if (modal === null) {
            return;
        }

        modal.addEventListener('shown.bs.modal', () => {
            const form = modal.querySelector('form');
            let formAutofocus = form.querySelectorAll('[autofocus]');
            if (formAutofocus.length < 1) {
                formAutofocus = form.querySelectorAll('input[type=text],input[type=date],textarea,select');
            }
            if (formAutofocus.length > 0) {
                formAutofocus[0].focus();
            }
        });
    }
}
