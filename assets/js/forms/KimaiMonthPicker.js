/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatePicker: single date selects (currently unused)
 */

import KimaiFormPlugin from "./KimaiFormPlugin";

export default class KimaiMonthPicker extends KimaiFormPlugin {

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
        const input = document.createElement('input');
        input.setAttribute('type','month');

        const notADateValue = 'not-a-month';
        input.setAttribute('value', notADateValue);

        if (input.value === notADateValue) {
            const polyfills = form.querySelectorAll('a.input-month-polyfill');
            polyfills.forEach(i => i.classList.toggle('d-none'));
            const inputs = form.querySelectorAll('input[type="month"]');
            inputs.forEach(i => i.classList.toggle('d-none'));
        }
    }

}
