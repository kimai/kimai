/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEditTimesheetForm: responsible for the most important form in the application
 */

import KimaiFormPlugin from "./KimaiFormPlugin";

/**
 * Used for simple copy from link to input action, e.g. the time and duration dropdowns
 * copy the selected values into their corresponding input.
 */
export default class KimaiCopyDataForm extends KimaiFormPlugin {

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form) // eslint-disable-line no-unused-vars
    {
        return true;
    }

    /**
     * @param {HTMLFormElement} form
     */
    activateForm(form)
    {
        if (this._eventHandler === undefined) {
            this._eventHandler = (event) => {
                let element = event.target;
                if (!element.matches('a[data-form-widget="copy-data"]')) {
                    element = element.parentNode; // mostly for icons
                }
                if (!element.matches('a[data-form-widget="copy-data"]') || element.dataset.target === undefined) {
                    return;
                }
                const target = document.querySelector(element.dataset.target);
                if (target === null) {
                    return;
                }
                target.value = element.dataset.value;
                if (element.dataset.event !== undefined) {
                    for (const event of element.dataset.event.split(' ')) {
                        target.dispatchEvent(new Event(event));
                        const form = target.closest('form');
                        if (form !== null) {
                            form.dispatchEvent(new Event(event));
                        }
                    }
                }
                event.preventDefault();
            };
        }
        form.addEventListener('click', this._eventHandler);
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        form.removeEventListener('click', this._eventHandler);
    }

}
