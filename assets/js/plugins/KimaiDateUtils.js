/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDateUtils: responsible for handling date specific tasks
 */

import KimaiPlugin from '../KimaiPlugin';
import moment from 'moment';

export default class KimaiDateUtils extends KimaiPlugin {

    getId() {
        return 'date';
    }

    /**
     * @param {string} dateTime
     * @returns {string}
     */
    getFormattedDate(dateTime) {
        return moment(dateTime).format(this.getConfiguration('formatDate'));
    }

    getWeekDaysShort() {
        return moment.localeData().weekdaysShort();
    }

    getMonthNames() {
        return moment.localeData().months();
    }

    formatDuration(since) {
        const duration = moment.duration(moment(new Date()).diff(moment(since)));

        return this.formatMomentDuration(duration);
    }

    formatSeconds(seconds) {
        const duration = moment.duration('PT' + seconds + 'S');

        return this.formatMomentDuration(duration);
    }

    /**
     * @param {moment.Duration} duration
     * @returns {string|*}
     */
    formatMomentDuration(duration) {
        const hours = parseInt(duration.asHours());
        const minutes = duration.minutes();

        return this.formatTime(hours, minutes);
    }

    formatTime(hours, minutes) {
        let format = this.getConfiguration('formatDuration');

        if (hours < 0 || minutes < 0) {
            hours = Math.abs(hours);
            minutes = Math.abs(minutes);
            if (minutes > 0 || hours > 0) {
                format = '-' + format;
            }
        }

        // special case for hours, as they can overflow the 24h barrier - Kimai does not support days as duration unit
        if (hours < 10) {
            hours = '0' + hours;
        }


        return format.replace('%h', hours).replace('%m', ('0' + minutes).substr(-2));
    }

    /**
     * @param {string} duration
     * @returns {int}
     */
    getSecondsFromDurationString(duration)
    {
        duration = duration.trim().toUpperCase();
        let momentDuration = moment.duration(NaN);

        if (duration.indexOf(':') !== -1) {
            momentDuration = moment.duration(duration);
        } else if (duration.indexOf('.') !== -1 || duration.indexOf(',') !== -1) {
            duration = duration.replace(/,/, '.');
            duration = (parseFloat(duration) * 3600).toString();
            momentDuration = moment.duration('PT' + duration + 'S');
        } else if (duration.indexOf('H') !== -1 || duration.indexOf('M') !== -1 || duration.indexOf('S') !== -1) {
            /* D for days does not work, because 'PT1H' but with days 'P1D' is used */
            momentDuration = moment.duration('PT' + duration);
        } else {
            let c = parseInt(duration);
            let d = parseInt(duration).toFixed();
            if (!isNaN(c) && duration === d) {
                duration = (c * 3600).toString();
                momentDuration = moment.duration('PT' + duration + 'S');
            }
        }

        if (!momentDuration.isValid()) {
            return 0;
        }

        return momentDuration.asSeconds();
    }

}
