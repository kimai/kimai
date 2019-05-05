/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiActiveRecordsDuration: updates active records on your personal timesheet view
 */

// Following the UMD template https://github.com/umdjs/umd/blob/master/templates/returnExportsGlobal.js
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define(['moment'], function (moment) {
            return (root.KimaiActiveRecordsDuration = factory(moment));
        });
    } else if (typeof module === 'object' && module.exports) {
        let moment = (typeof window != 'undefined') ? window.moment : undefined;
        if (!moment) {
            moment = require('moment');
        }
        module.exports = factory(moment);
    } else {
        root.KimaiActiveRecordsDuration = factory(root.moment);
    }
}(typeof self !== 'undefined' ? self : this, function (moment) {

    class KimaiActiveRecordsDuration {
        constructor(selector) {
            this.selector = selector;
        }

        registerUpdates(timeout) {
            let self = this;
            window.setTimeout(
                function() {
                    self.updateRecords().registerUpdates(timeout);
                },
                timeout
            );
        }

        updateRecords() {
            let durations = [];
            for(let record of document.querySelectorAll(this.selector)) {
                const since = record.getAttribute('data-since');
                const format = record.getAttribute('data-format');
                const duration = this.getDuration(since, format);
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

        getDuration(since, format) {
            let duration = moment.duration(moment(new Date()).diff(moment(since)));

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

    return KimaiActiveRecordsDuration;

}));
