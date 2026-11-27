/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEditAfterStop: opens the edit dialog of a time entry after it was stopped
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiEditAfterStop extends KimaiPlugin {

    /**
     * @returns {string}
     */
    getId()
    {
        return 'edit-after-stop';
    }

    init()
    {
        if (!this.getConfiguration('editAfterStop')) {
            return;
        }

        document.addEventListener('kimai.timesheetStop', (event) => {
            const timesheet = event.detail;
            if (timesheet === null || timesheet === undefined || timesheet.id === undefined) {
                return;
            }

            // only own entries can be edited with the "timesheet_edit" route
            if (timesheet.user !== this.getConfiguration('user').id) {
                return;
            }

            const url = this.getConfiguration('timesheetEdit').replace('000', timesheet.id);
            const modalElement = document.getElementById('remote_form_modal');
            if (modalElement !== null) {
                modalElement.addEventListener('shown.bs.modal', () => {
                    const description = modalElement.querySelector('textarea[name$="[description]"]');
                    if (description !== null) {
                        description.focus();
                    }
                }, {once: true});
            }

            this.getPlugin('modal').openUrlInModal(url);
        });
    }

}
