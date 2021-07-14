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

    getFormData(form) {
        let serialized = [];

        // Loop through each field in the form
        for (let i = 0; i < form.elements.length; i++) {

            let field = form.elements[i];

            // Don't serialize a couple of field types (button and submit are important to exclude, eg. invoice preview would fail otherwise)
            if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') {
                continue;
            }

            // If a multi-select, get all selections
            if (field.type === 'select-multiple') {
                for (var n = 0; n < field.options.length; n++) {
                    if (!field.options[n].selected) {
                        continue;
                    }
                    serialized.push({
                        name: field.name,
                        value: field.options[n].value
                    });
                }
            } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                serialized.push({
                    name: field.name,
                    value: field.value
                });
            }
        }

        return serialized;
    }

    convertFormDataToQueryString(formData) {
        let serialized = [];

        for (let row of formData) {
            serialized.push(encodeURIComponent(row.name) + "=" + encodeURIComponent(row.value));
        }

        return serialized.join('&');
    }
}
