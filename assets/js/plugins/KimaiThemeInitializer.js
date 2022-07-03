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
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiThemeInitializer extends KimaiPlugin {

    init() {
        // the tooltip do not use data-bs-toggle="tooltip" so they can be mixed with data-toggle="modal"
        [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]')).map(function (tooltipTriggerEl) {
            return new Tooltip(tooltipTriggerEl);
        });

        // activate all form plugins
        /** @type {KimaiForm} FORMS */
        const FORMS = this.getContainer().getPlugin('form');
        FORMS.activateForm('div.page-wrapper form');
        FORMS.activateForm('form.searchform');

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

        document.addEventListener(eventNameShow, (event) => {
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

        document.addEventListener(eventNameHide, (event) => {
            if (this.overlay !== null) {
                this.overlay.remove();
                this.overlay = null;
            }
        });
    }

    /**
     * workaround for autofocus attribute, as the modal "steals" it
     *
     * @param {string} selector
     */
    registerModalAutofocus(selector) {
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

    /**
     * Check if the current device is a mobile device (targeting the bootstrip xs breakpoint size).
     *
     * @returns {boolean}
     */
    isMobile() {
        const width = Math.max(
            document.documentElement.clientWidth,
            window.innerWidth || 0
        )

        return width < 576;
    }
}
