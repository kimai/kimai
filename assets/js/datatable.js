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

// Following the UMD template https://github.com/umdjs/umd/blob/master/templates/returnExportsGlobal.js
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], function (jquery) {
            return (root.KimaiDatatableColumnView = factory(jquery));
        });
    } else if (typeof module === 'object' && module.exports) {
        let jQuery = (typeof window != 'undefined') ? window.jQuery : undefined;
        if (!jQuery) {
            jQuery = require('jquery');
            if (!jQuery.fn) {
                jQuery.fn = {};
            }
        }
        module.exports = factory(jQuery);
    } else {
        root.KimaiDatatableColumnView = factory(root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function ($) {

    /**
     * This is my first approach on ES6, so it can be optimized.
     * Please: show your JS skills and teach a PHP backend developer how to do it properly, sent a PR!
     *
     * BTW: I tried to get rid of it, but jQuery is still required for the bootstrap modal ...
     */
    class KimaiDatatableColumnView {

        constructor(selector) {
            this.id = selector;
            this.modal = document.getElementById('modal_' + selector);
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
                    self.changeVisibility(checkbox.getAttribute('name'));
                });
            }
        }

        saveVisibility() {
            const form = this.modal.getElementsByTagName('form')[0];
            let settings = {};
            for (let checkbox of form.querySelectorAll('input[type=checkbox]')) {
                settings[checkbox.getAttribute('name')] = checkbox.checked;
            }
            Cookies.set(form.getAttribute('name'), JSON.stringify(settings), {expires: 365});
            $(this.modal).modal('toggle');
        }

        resetVisibility() {
            const form = this.modal.getElementsByTagName('form')[0];
            Cookies.remove(form.getAttribute('name'));
            for (let checkbox of form.querySelectorAll('input[type=checkbox]')) {
                if (!checkbox.checked) {
                    checkbox.click();
                }
            }
            $(this.modal).modal('toggle');
        }

        changeVisibility(columnName) {
            const table = document.getElementById('datatable_' + this.id).getElementsByClassName('dataTable')[0];
            let column = 0;
            let foundColumn = false;
            for (let columnElement of table.getElementsByTagName('th')) {
                if (columnElement.getAttribute('data-field') === columnName) {
                    foundColumn = true;
                    break;
                }
                column++;
            }

            if (!foundColumn) {
                console.error('Could not find column: ' + columnName);
                return;
            }

            for (let rowElement of table.getElementsByTagName('tr')) {
                rowElement.children[column].classList.toggle('hidden');
            }
        }

    }

    return KimaiDatatableColumnView;

}));

