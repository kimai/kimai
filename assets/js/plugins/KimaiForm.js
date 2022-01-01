/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiForm: basic functions for all forms
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiForm extends KimaiPlugin {

    getId() {
        return 'form';
    }

    activateForm(formSelector, container) {
        this.getContainer().getPlugin('date-range-picker').activateDateRangePicker(formSelector);
        this.getContainer().getPlugin('date-time-picker').activateDateTimePicker(formSelector);
        this.getContainer().getPlugin('date-picker').activateDatePicker(formSelector);
        this.getContainer().getPlugin('autocomplete').activateAutocomplete(formSelector);
        this.getContainer().getPlugin('form-select').activateSelectPicker(formSelector, container);
    }
    
    destroyForm(formSelector) {
        this.getContainer().getPlugin('form-select').destroySelectPicker(formSelector);
        this.getContainer().getPlugin('autocomplete').destroyAutocomplete(formSelector);
        this.getContainer().getPlugin('date-picker').destroyDatePicker(formSelector);
        this.getContainer().getPlugin('date-time-picker').destroyDateTimePicker(formSelector);
        this.getContainer().getPlugin('date-range-picker').destroyDateRangePicker(formSelector);
    }

    /**
     * @param {HTMLFormElement} form
     * @param {Object} overwrites
     * @returns {string}
     */
    convertFormDataToQueryString(form, overwrites = {})
    {
        let serialized = [];
        let data = new FormData(form);

        for (const key in overwrites) {
            data.set(key, overwrites[key]);
        }

        for (let row of data) {
            serialized.push(encodeURIComponent(row[0]) + "=" + encodeURIComponent(row[1]));
        }

        return serialized.join('&');
    }
}
