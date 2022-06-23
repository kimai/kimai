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

    getId()
    {
        return 'form';
    }

    activateForm(formSelector)
    {
        const form = document.querySelector(formSelector);
        if (form === null) {
            return;
        }

        // do not init in init(), but just in case there is a page which does not have a form
        if (this.dateTimeWidgetHandler === undefined) {
            this.dateTimeWidgetHandler = (event) => {
                let linkTarget = event.target;

                // the HTML structure is <a href="" data-format="HH:MM" data-target="formElementId"><i class="icon"></i></a>
                if (linkTarget.tagName.toUpperCase() === 'I') {
                    linkTarget = linkTarget.parentElement;
                }

                const formElement = document.getElementById(linkTarget.dataset.target);
                if (!formElement.disabled) {
                    formElement.value = this.getDateUtils().format(linkTarget.dataset.format, null);
                    formElement.dispatchEvent(new Event('change'));
                }

                return false;
            };
        }

        [].slice.call(document.querySelectorAll(formSelector + ' a.kimai-date-widget')).map((element) => {
            element.addEventListener('click', this.dateTimeWidgetHandler);
        });

        // TODO do not register them as global plugins, but only as plugins for each form inside this class
        this.getPlugin('date-range-picker').activateDateRangePicker(formSelector);
        this.getPlugin('date-time-picker').activateDateTimePicker(formSelector);
        this.getPlugin('date-picker').activateDatePicker(formSelector);
        this.getPlugin('autocomplete').activateAutocomplete(formSelector);
        this.getPlugin('form-select').activateSelectPicker(formSelector);

        switch (form.name) {
            case 'timesheet_edit_form':
            case 'timesheet_admin_edit_form':
                this.getPlugin('edit-timesheet-form').activateForm(form);
                break;
        }
    }

    destroyForm(formSelector)
    {
        const form = document.querySelector(formSelector);
        if (form === null) {
            return;
        }

        if (this.dateTimeWidgetHandler !== undefined) {
            [].slice.call(document.querySelectorAll(formSelector + ' a.kimai-date-widget')).map((element) => {
                element.removeEventListener('click', this.dateTimeWidgetHandler);
            });

            delete this.dateTimeWidgetHandler;
        }

        this.getPlugin('form-select').destroySelectPicker(formSelector);
        this.getPlugin('autocomplete').destroyAutocomplete(formSelector);
        this.getPlugin('date-picker').destroyDatePicker(formSelector);
        this.getPlugin('date-time-picker').destroyDateTimePicker(formSelector);
        this.getPlugin('date-range-picker').destroyDateRangePicker(formSelector);

        switch (form.name) {
            case 'timesheet_edit_form':
            case 'timesheet_admin_edit_form':
                this.getPlugin('edit-timesheet-form').destroyForm(form);
                break;
        }
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
