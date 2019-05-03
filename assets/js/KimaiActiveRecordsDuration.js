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
        constructor(selector, timeout) {
            this.selector = selector;
        }

        registerUpdates(timeout) {
            let self = this;
            window.setTimeout(
                function() {
                    self.updateRecords(self.selector);
                    self.registerUpdates(timeout);
                },
                timeout
            );
        }

        updateRecords(selector) {
            let durations = [];
            for(let record of document.querySelectorAll(selector)) {
                const since = record.getAttribute('data-since');
                const format = record.getAttribute('data-format');
                const duration = this.getDuration(since, format);
                durations.push(duration);
                record.textContent = duration;
            }

            if (durations.length === 0) {
                return;
            }

            let title = '';
            let prefix = '';
            if (durations.length > 1) {
                title += durations.shift();
                prefix = ' | ';
            }
            for (let duration of durations) {
                title += prefix + duration;
            }
            document.title = title;
        }

        getDuration(since, format) {
            let duration = moment.duration(moment(new Date()).diff(moment(since)));

            let hours = parseInt(duration.asHours());
            let minutes = duration.minutes();
            let seconds = duration.seconds();

            return format.replace('%h', hours).replace('%m', minutes).replace('%s', seconds);
        }
    }

    return KimaiActiveRecordsDuration;

}));
