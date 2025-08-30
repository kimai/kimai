/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiMultiUpdateForm: handle the multi update checkbox list and form
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiMultiUpdateTable extends KimaiPlugin {

    getId()
    {
        return 'datatable-batch-action';
    }

    init()
    {
        if (document.getElementsByClassName('multi_update_all').length === 0) {
            return;
        }

        // we have to attach it to the "page-body" div, because section.content can be replaced
        // via KimaiDatable and everything inside will be removed, including event listeners
        const element = document.querySelector('div.page-body');
        element.addEventListener('change', (event) => {
            if (event.target.matches('.multi_update_all')) {
                this.toggle(event.target.checked, event.target.closest('table'));
                event.stopPropagation();
            } else if (event.target.matches('.multi_update_single')) {
                // single checkboxes in front of each row
                this._toggleDatatable(event.target.closest('table'));
                event.stopPropagation();
            }
        });

        element.addEventListener('click', (event) => {
            if (event.target.matches('.multi_update_table_action')) {
                const selectedButton = event.target;
                const form = selectedButton.form;
                const ids = form.querySelector('.multi_update_ids').value.split(',');
                const question = form.dataset['question'].replace(/%action%/, selectedButton.textContent).replace(/%count%/, ids.length.toString());

                /** @type {KimaiAlert} ALERT */
                const ALERT = this.getPlugin('alert');
                ALERT.question(question, function(value) {
                    if (value) {
                        form.action = selectedButton.dataset['href'];
                        form.submit();
                    }
                });
            }
        });
    }

    /**
     * @param {boolean} checked
     * @param {HTMLTableElement} table
     */
    toggle(checked, table)
    {
        for (const element of table.querySelectorAll('.multi_update_single')) {
            element.checked = checked;
        }
        this._toggleDatatable(table);
    }

    /**
     * @param {boolean} checked
     */
    toggleAll(checked)
    {
        for (const element of document.querySelectorAll('.multi_update_all')) {
            this._toggleAll(checked, element);
        }
    }

    /**
     * @param {boolean} checked
     * @param {string} name
     */
    toggleByName(checked, name)
    {
        for (const element of document.querySelectorAll('#multi_update_all_' + name)) {
            this._toggleAll(checked, element);
        }
    }

    /**
     * @param {boolean} checked
     * @param {Element} name
     */
    _toggleAll(checked, element)
    {
        element.checked = checked;
        this.toggle(checked, element.closest('table'));
    }

    /**
     * @param {HTMLTableElement} table
     * @private
     */
    _toggleDatatable(table)
    {
        const card = table.closest('div.card.data_table');

        let ids = [];
        for (const box of table.querySelectorAll('input.multi_update_single:checked')) {
            ids.push(box.value);
        }

        card.querySelector('.multi_update_ids').value = ids.join(',');

        if (ids.length > 0) {
            for (const element of card.querySelectorAll('.multi_update_form_hide')) {
                element.style.setProperty('display', 'none', 'important');
            }
            card.querySelector('form.multi_update_form').style.display = null;//'block';
        } else {
            card.querySelector('form.multi_update_form').style.setProperty('display', 'none', 'important');
            for (const element of card.querySelectorAll('.multi_update_form_hide')) {
                element.style.display = null;
            }
        }
    }
    
}
