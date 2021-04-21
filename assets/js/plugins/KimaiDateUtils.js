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

    formatMomentDuration(duration) {
        const hours = parseInt(duration.asHours()).toString();
        const minutes = duration.minutes();
        const seconds = duration.seconds();

        return this.formatTime(hours, minutes, seconds);
    }

    formatTime(hours, minutes, seconds) {
        if (hours < 0 || minutes < 0 || seconds < 0) {
            return '?';
        }

        // special case for hours, as they can overflow the 24h barrier - Kimai does not support days as duration unit
        if (hours.length === 1) {
            hours = '0' + hours;
        }

        const format = this.getConfiguration('formatDuration');

        return format.replace('%h', hours).replace('%m', ('0'+minutes).substr(-2)).replace('%s', ('0'+seconds).substr(-2));
    }

}
