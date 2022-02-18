/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiActiveRecordsDuration: activate the updates for all active timesheet records on this page
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiActiveRecordsDuration extends KimaiPlugin {

    getId() {
        return 'timesheet-duration';
    }

    init() {
        this.updateBrowserTitle = !!this.getConfiguration('updateBrowserTitle');
        this.updateRecords();
        const self = this;
        const handle = function() { self.updateRecords(); };
        this._updatesHandler = setInterval(handle, 10000);
        // this will probably not work as expected, as other event-handler might need longer to update the DOM
        document.addEventListener('kimai.timesheetUpdate', handle);
    }

    unregisterUpdates() {
        clearInterval(this._updatesHandler);
    }

    updateRecords() {
        let durations = [];
        const activeRecords = document.querySelectorAll('[data-since]:not([data-since=""])');

        if (activeRecords.length === 0) {
            if (this.updateBrowserTitle) {
                if (document.body.dataset['title'] === undefined) {
                    this.updateBrowserTitle = false;
                } else {
                    document.title = document.body.dataset['title'];
                }
            }
            return;
        }

        const DATE = this.getPlugin('date');

        for (let record of activeRecords) {
            const since = record.dataset['since'];
            const duration = DATE.formatDuration(since);
            // only use the ones from the menu for the title
            if (record.dataset['replacer'] !== undefined && record.dataset['title'] !== null && duration !== '?') {
                durations.push(duration);
            }
            // but update all on the page (running entries in list pages)
            record.textContent = duration;
        }

        if (durations.length === 0) {
            return;
        }

        if (!this.updateBrowserTitle) {
            return;
        }

        let title = durations.shift();
        let prefix = ' | ';

        for (let duration of durations.slice(0, 2)) {
            title += prefix + duration;
        }
        document.title = title;
    }
}
