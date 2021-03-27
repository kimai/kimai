/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatatableColumnView: manages the visibility of data-table columns in cookies
 */

import Cookies from 'js-cookie';
import jQuery from 'jquery';
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
            checkbox.addEventListener('click', function () {
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
        Cookies.set(form.getAttribute('name'), JSON.stringify(settings), {expires: 365, SameSite: 'Strict'});
        jQuery(this.modal).modal('toggle');
    }

    resetVisibility() {
        const form = this.modal.getElementsByTagName('form')[0];
        Cookies.remove(form.getAttribute('name'));
        for (let checkbox of form.querySelectorAll('input[type=checkbox]')) {
            if (!checkbox.checked) {
                checkbox.click();
            }
        }
        jQuery(this.modal).modal('toggle');
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
                return;
            }

            for (let rowElement of table.getElementsByTagName('tr')) {
                if (rowElement.children[column] === undefined) {
                    continue;
                }

                if (checked) {
                    rowElement.children[column].classList.remove('hidden');
                } else {
                    rowElement.children[column].classList.add('hidden');
                }
            }
        }
    }

}
