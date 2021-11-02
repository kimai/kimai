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
import KimaiCookies from "../widgets/KimaiCookies";
import { Modal } from 'bootstrap';

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
        this.bindButtons();
    }

    bindButtons() {
        let self = this;
        this.modal.querySelector('button[data-type=save]').addEventListener('click', function() {
            self.saveVisibility();
        });
        this.modal.querySelector('button[data-type=reset]').addEventListener('click', function() {
            self.resetVisibility();
        });
        for (let checkbox of this.modal.querySelectorAll('form input[type=checkbox]')) {
            checkbox.addEventListener('change', function () {
                self.changeVisibility(checkbox.getAttribute('name'), checkbox.checked);
            });
        }
    }

    saveVisibility() {
        const form = this.modal.getElementsByTagName('form')[0];
        let settings = {};
        for (let checkbox of form.querySelectorAll('input[type=checkbox]')) {
            settings[checkbox.getAttribute('name')] = checkbox.checked;
        }
        KimaiCookies.set(form.getAttribute('name'), JSON.stringify(settings), {expires: 365, SameSite: 'Strict'});
        Modal.getInstance(this.modal).hide();
    }

    resetVisibility() {
        const form = this.modal.getElementsByTagName('form')[0];
        KimaiCookies.remove(form.getAttribute('name'));
        this.getPlugin('event').trigger('kimai.reset_column_visibility', {'datatable': this.id})
        Modal.getInstance(this.modal).hide();
    }

    changeVisibility(columnName, checked) {
        const tables = document.getElementsByClassName('datatable_' + this.id);
        for (let tableBox of tables) {
            let column = 0;
            let foundColumn = false;
            let table = tableBox.getElementsByClassName('dataTable')[0];
            for (let columnElement of table.getElementsByTagName('th')) {
                if (columnElement.getAttribute('data-field') === columnName) {
                    foundColumn = true;
                    break;
                }

                if (columnElement.getAttribute('colspan') !== null) {
                    console.log('Tables with colspans are not supported!');
                }

                column++;
            }

            if (!foundColumn) {
                console.log('Could not find column: ' + columnName);
                return;
            }

            let targetClasses = null;

            for (let rowElement of table.getElementsByTagName('tr')) {
                if (rowElement.children[column] === undefined) {
                    continue;
                }


                if (targetClasses === null) {
                    let removeClass = '-none';
                    let addClass = 'd-table-cell';

                    if (!checked) {
                        removeClass = '-table-cell';
                        addClass = 'd-none';
                    }

                    const list = rowElement.children[column].classList;
                    targetClasses = '';
                    list.forEach(
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

                rowElement.children[column].className = targetClasses;
            }
        }
    }

}
