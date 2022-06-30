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

    init() {
        const updateAll = document.getElementById('multi_update_all');
        if (updateAll === null) {
            return;
        }

        updateAll.addEventListener('change', (event) => {
            const checked = event.target.checked;
            for (const element of document.querySelectorAll('.multi_update_single')) {
                element.checked = checked;
            }
            this.toggleForm();
            event.stopPropagation();
        });

        // single checkboxes in front of each row
        for (const element of document.querySelectorAll('div.data_table')) {
            element.addEventListener('change', (event) => {
                if (event.target.matches('.multi_update_single')) {
                    this.toggleForm();
                    event.stopPropagation();
                }
            });
            element.addEventListener('click', (event) => {
                if (event.target.matches('.multi_update_table_action')) {
                    const selectedItem = event.target;
                    const selectedVal = selectedItem.dataset['href'];
                    const form = document.getElementById('multi_update_form');
                    const selectedText = selectedItem.textContent;
                    const ids = this.getSelectedIds();
                    const question = form.dataset['question'].replace(/%action%/, selectedText).replace(/%count%/, ids.length.toString());

                    /** @type {KimaiAlert} ALERT */
                    const ALERT = this.getPlugin('alert');
                    ALERT.question(question, function(value) {
                        if (value) {
                            form.action = selectedVal;
                            form.submit();
                        }
                    });
                }
            });
        }
    }
    
    getSelectedIds()
    {
        let ids = [];
        for (const box of document.querySelectorAll('input.multi_update_single:checked')) {
            ids.push(box.value);
        }

        return ids;
    }
    
    toggleForm() 
    {
        const ids = this.getSelectedIds();
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
