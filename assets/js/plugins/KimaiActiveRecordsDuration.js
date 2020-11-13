/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiActiveRecordsDuration: activate the updates for all active timesheet records on this page
 */

import moment from 'moment';
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiActiveRecordsDuration extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'timesheet-duration';
    }

    init() {
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
        const activeRecords = document.querySelectorAll(this.selector);

        if (activeRecords.length === 0) {
            document.title = document.querySelector('body').dataset['title'];
            return;
        }

        for(let record of activeRecords) {
            const since = record.getAttribute('data-since');
            const format = record.getAttribute('data-format');
            const duration = this.formatDuration(since, format);
            if (record.getAttribute('data-title') !== null && duration !== '?') {
                durations.push(duration);
            }
            record.textContent = duration;
        }

        if (durations.length === 0) {
            return;
        }

        let title = durations.shift();
        let prefix = ' | ';

        for (let duration of durations.slice(0, 2)) {
            title += prefix + duration;
        }
        document.title = title;
    }

    formatDuration(since, format) {
        const duration = moment.duration(moment(new Date()).diff(moment(since)));

        let hours = parseInt(duration.asHours()).toString();
        let minutes = duration.minutes();
        let seconds = duration.seconds();

        if (hours < 0 || minutes < 0 || seconds < 0) {
            return '?';
        }

        // special case for hours, as they can overflow the 24h barrier - Kimai does not support days as duration unit
        if (hours.length === 1) {
            hours = '0' + hours;
        }

        return format.replace('%h', hours).replace('%m', ('0'+minutes).substr(-2)).replace('%s', ('0'+seconds).substr(-2));
    }
}
