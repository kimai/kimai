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
                    formElement.dispatchEvent(new Event('change', {bubbles: true}));
                }

                event.preventDefault();

                return false;
            };
        }

        [].slice.call(document.querySelectorAll(formSelector + ' a.kimai-date-widget')).map((element) => {
            element.addEventListener('click', this.dateTimeWidgetHandler);
        });

        // used for the duration plugin, but can be used for more
        [].slice.call(document.querySelectorAll(formSelector + ' a[data-copy-target]')).map((element) => {
            if (element.dataset.copyTarget !== undefined) {
                element.addEventListener('click', (event) => {
                    const target = document.querySelector(element.dataset.copyTarget);
                    target.value = element.dataset.copyValue;
                    for (const event of element.dataset.copyEvent.split(' ')) {
                        target.dispatchEvent(new Event(event));
                    }
                    event.preventDefault();
                });
            }
        });

        // TODO do not register them as global plugins, but only as plugins for each form inside this class
        this.getPlugin('date-range-picker').activate(formSelector);
        this.getPlugin('date-picker').activate(formSelector);
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
        this.getPlugin('date-picker').destroy(formSelector);
        this.getPlugin('date-range-picker').destroy(formSelector);

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
     * @param {boolean} removeEmpty
     * @returns {string}
     */
    convertFormDataToQueryString(form, overwrites = {}, removeEmpty = false)
    {
        let serialized = [];
        let data = new FormData(form);

        for (const key in overwrites) {
            data.set(key, overwrites[key]);
        }

        for (let row of data) {
            if (!removeEmpty || row[1] !== '') {
                serialized.push(encodeURIComponent(row[0]) + "=" + encodeURIComponent(row[1]));
            }
        }

        return serialized.join('&');
    }
}
