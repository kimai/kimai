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
 */
export default class KimaiDateNowForm extends KimaiFormPlugin {

    init()
    {
        this.selector = 'a[data-form-widget="date-now"]';
    }

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
        [].slice.call(form.querySelectorAll(this.selector)).map((element) => {
            if (element.dataset.format !== undefined && element.dataset.target !== undefined) {
                if (this._eventHandler === undefined) {
                    this._eventHandler = (event) => {
                        const linkTarget = event.currentTarget;

                        const formElement = document.getElementById(linkTarget.dataset.target);
                        if (!formElement.disabled) {
                            formElement.value = this.getDateUtils().format(linkTarget.dataset.format, null);
                            formElement.dispatchEvent(new Event('change', {bubbles: true}));
                        }

                        event.preventDefault();
                    };
                }
                element.addEventListener('click', this._eventHandler);
            }
        });

    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        [].slice.call(form.querySelectorAll(this.selector)).map((element) => {
            if (element.dataset.format !== undefined && element.dataset.target !== undefined) {
                element.removeEventListener('click', this._eventHandler);
            }
        });
    }

}
