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
        this.id = dataTable.getAttribute(this.dataAttribute);
        this.modal = document.getElementById('modal_' + this.id);
        this.modal.addEventListener('show.bs.modal', event => {
            this.evaluateCheckboxes();
        });
        this.modal.querySelector('button[data-type=save]').addEventListener('click', event => {
            this.saveVisibility();
        });
        this.modal.querySelector('button[data-type=reset]').addEventListener('click', event => {
            this.resetVisibility(event.currentTarget);
        });
        this.modal.querySelectorAll('input[name=datatable_profile]').forEach(element => {
            element.addEventListener('change', event => {
                const form = this.modal.getElementsByTagName('form')[0];
                fetch(element.getAttribute('data-href'), {
                    method: form.getAttribute('method'),
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: this.getPlugin('form').convertFormDataToQueryString(form),
                })
                .then(data => {
                    // the local storage is read in the login screen to set a cookie,
                    // which triggers the session switch in ProfileSubscriber
                    localStorage.setItem('kimai_profile', element.getAttribute('value'));
                    document.location.reload();
                })
                .catch((error) => {
                    form.setAttribute('action', element.getAttribute('data-href'));
                    form.submit();
                });
            });
        });
        for (let checkbox of this.modal.querySelectorAll('form input[type=checkbox]')) {
            checkbox.addEventListener('change', event =>  {
                this.changeVisibility(checkbox.getAttribute('name'), checkbox.checked);
            });
        }
    }

    evaluateCheckboxes() {
        const form = this.modal.getElementsByTagName('form')[0];
        const table = document.getElementsByClassName('datatable_' + this.id)[0];
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

    saveVisibility() {
        const form = this.modal.getElementsByTagName('form')[0];

        fetch(form.getAttribute('action'), {
            method: form.getAttribute('method'),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: this.getPlugin('form').convertFormDataToQueryString(form),
        })
        .then(data => {
            document.location.reload();
        })
        .catch((error) => {
            form.submit();
        });
    }

    resetVisibility(button) {
        const form = this.modal.getElementsByTagName('form')[0];

        fetch(button.getAttribute('formaction'), {
            method: form.getAttribute('method'),
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: this.getPlugin('form').convertFormDataToQueryString(form),
        })
        .then(data => {
            document.location.reload();
        })
        .catch((error) => {
            form.setAttribute('action', button.getAttribute('formaction'));
            form.submit();
        });
    }

    changeVisibility(columnName, checked) {
        for (const tableBox of document.getElementsByClassName('datatable_' + this.id)) {
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
                        function (name, index, listObj) {
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
