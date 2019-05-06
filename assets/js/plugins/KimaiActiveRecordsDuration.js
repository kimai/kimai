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

    init() {
        this.updateRecords();
        this.registerUpdates(10000);
    }

    registerUpdates(interval) {
        let self = this;
        this._updatesHandler = setInterval(
            function() {
                self.updateRecords();
            },
            interval
        );
    }

    unregisterUpdates() {
        clearInterval(this._updatesHandler);
    }

    updateRecords() {
        let durations = [];
        for(let record of document.querySelectorAll(this.selector)) {
            const since = record.getAttribute('data-since');
            const format = record.getAttribute('data-format');
            const duration = KimaiActiveRecordsDuration._getDuration(since, format);
            if (record.getAttribute('data-title') !== null) {
                durations.push(duration);
            }
            record.textContent = duration;
        }

        if (durations.length === 0) {
            return this;
        }

        let title = durations.shift();
        let prefix = ' | ';

        for (let duration of durations.slice(0, 2)) {
            title += prefix + duration;
        }
        document.title = title;

        return this;
    }

    static _getDuration(since, format) {
        const duration = moment.duration(moment(new Date()).diff(moment(since)));

        let hours = parseInt(duration.asHours()).toString();
        let minutes = duration.minutes();
        let seconds = duration.seconds();

        // special case for hours, as they can overflow the 24h barrier - Kimai does not support days as duration unit
        if (hours.length === 1) {
            hours = '0' + hours;
        }

        return format.replace('%h', hours).replace('%m', ('0'+minutes).substr(-2)).replace('%s', ('0'+seconds).substr(-2));
    }
}
