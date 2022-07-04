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
        if (document.getElementById('multi_update_all') === null) {
            return;
        }

        // we have to attach it to the "page-body" div, because section.content can be replaced
        // via KimaiDatable and everything inside will be removed, including event listeners
        const element = document.querySelector('div.page-body');
        element.addEventListener('change', (event) => {
            if (event.target.matches('#multi_update_all')) {
                // the "check all" checkbox in the upper start corner of the table
                const checked = event.target.checked;
                for (const element of document.querySelectorAll('.multi_update_single')) {
                    element.checked = checked;
                }
                this._toggleForm();
                event.stopPropagation();
            } else if (event.target.matches('.multi_update_single')) {
                // single checkboxes in front of each row
                this._toggleForm();
                event.stopPropagation();
            }
        });

        element.addEventListener('click', (event) => {
            if (event.target.matches('.multi_update_table_action')) {
                const selectedItem = event.target;
                const ids = this._getSelectedIds();
                const form = document.getElementById('multi_update_form');
                const question = form.dataset['question'].replace(/%action%/, selectedItem.textContent).replace(/%count%/, ids.length.toString());

                /** @type {KimaiAlert} ALERT */
                const ALERT = this.getPlugin('alert');
                ALERT.question(question, function(value) {
                    if (value) {
                        const form = document.getElementById('multi_update_form');
                        form.action = selectedItem.dataset['href'];
                        form.submit();
                    }
                });
            }
        });
    }
    
    _getSelectedIds()
    {
        let ids = [];
        for (const box of document.querySelectorAll('input.multi_update_single:checked')) {
            ids.push(box.value);
        }

        return ids;
    }
    
    _toggleForm()
    {
        const ids = this._getSelectedIds();
        document.getElementById('multi_update_table_entities').value = ids.join(',');

        if (ids.length > 0) {
            for (const element of document.getElementsByClassName('multi_update_form_hide')) {
                element.style.setProperty('display', 'none', 'important');
            }
            document.getElementById('multi_update_form').style.display = null;//'block';
        } else {
            document.getElementById('multi_update_form').style.setProperty('display', 'none', 'important');
            for (const element of document.getElementsByClassName('multi_update_form_hide')) {
                element.style.display = null;
            }
        }
    }
    
}
