/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatatableColumnView: manages the visibility of data-table columns in cookies
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiDatatableColumnView extends KimaiPlugin {

    constructor(dataAttribute) {
        super();
        this.dataAttribute = dataAttribute;
    }

    getId() {
        return 'datatable-column-visibility';
    }

    init() {
        let dataTable = document.querySelector('[' + this.dataAttribute + ']');
        if (dataTable === null) {
            return;
        }
        this._id = dataTable.getAttribute(this.dataAttribute);
        this._modal = document.getElementById('modal_' + this._id);
        this._modal.addEventListener('show.bs.modal', () => {
            this._evaluateCheckboxes();
        });
        this._modal.querySelector('button[data-type=save]').addEventListener('click', () => {
            this._saveVisibility();
        });
        this._modal.querySelector('button[data-type=reset]').addEventListener('click', (event) => {
            this._resetVisibility(event.currentTarget);
        });
        this._modal.querySelectorAll('input[name=datatable_profile]').forEach(element => {
            element.addEventListener('change', () => {
                const form = this._modal.getElementsByTagName('form')[0];
                this.fetchForm(form, {}, element.getAttribute('data-href'))
                .then(() => {
                    // the local storage is read in the login screen to set a cookie,
                    // which triggers the session switch in ProfileSubscriber
                    localStorage.setItem('kimai_profile', element.getAttribute('value'));
                    document.location.reload();
                })
                .catch(() => {
                    form.setAttribute('action', element.getAttribute('data-href'));
                    form.submit();
                });
            });
        });
        for (let checkbox of this._modal.querySelectorAll('form input[type=checkbox]')) {
            checkbox.addEventListener('change', () =>  {
                this._changeVisibility(checkbox.getAttribute('name'), checkbox.checked);
            });
        }
    }

    _evaluateCheckboxes() {
        const form = this._modal.getElementsByTagName('form')[0];
        const table = document.getElementsByClassName('datatable_' + this._id)[0];
        for (let columnElement of table.getElementsByTagName('th')) {
            const fieldName = columnElement.getAttribute('data-field');
            if (fieldName === null) {
                continue;
            }
            const checkbox = form.querySelector('input[name=' + fieldName + ']');
            if (checkbox === null) {
                continue;
            }
            checkbox.checked = window.getComputedStyle(columnElement).display !== 'none';
        }
    }

    _saveVisibility() {
        const form = this._modal.getElementsByTagName('form')[0];

        this.fetchForm(form)
        .then(() => {
            document.location.reload();
        })
        .catch(() => {
            form.submit();
        });
    }

    _resetVisibility(button) {
        const form = this._modal.getElementsByTagName('form')[0];

        this.fetchForm(form, {}, button.getAttribute('formaction'))
        .then(() => {
            document.location.reload();
        })
        .catch(() => {
            form.setAttribute('action', button.getAttribute('formaction'));
            form.submit();
        });
    }

    _changeVisibility(columnName, checked) {
        for (const tableBox of document.getElementsByClassName('datatable_' + this._id)) {
            let targetClasses = null;
            for (let element of tableBox.getElementsByClassName('col_' + columnName)) {
                // only calculate that once and re-use the cached class list
                if (targetClasses === null) {
                    let removeClass = '-none';
                    let addClass = 'd-table-cell';

                    if (!checked) {
                        removeClass = '-table-cell';
                        addClass = 'd-none';
                    }

                    targetClasses = '';
                    element.classList.forEach(
                        function (name, index, listObj) {  // eslint-disable-line no-unused-vars
                            if (name.indexOf(removeClass) === -1) {
                                targetClasses += ' ' + name;
                            }
                        }
                    );

                    if (targetClasses.indexOf(addClass) === -1) {
                        targetClasses += ' ' + addClass;
                    }
                }

                element.className = targetClasses;
            }
        }
    }

}
