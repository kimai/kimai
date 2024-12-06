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
                // the "check all" checkbox in the upper start corner of the table
                const checked = event.target.checked;
                const table = event.target.closest('table');
                for (const element of table.querySelectorAll('.multi_update_single')) {
                    element.checked = checked;
                }
                this._toggleForm(table);
                event.stopPropagation();
            } else if (event.target.matches('.multi_update_single')) {
                // single checkboxes in front of each row
                this._toggleForm(event.target.closest('table'));
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
     * @param {HTMLTableElement} table
     * @private
     */
    _toggleForm(table)
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
