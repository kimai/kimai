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
import { DateTime, Duration } from 'luxon';

export default class KimaiDateUtils extends KimaiPlugin {

    getId()
    {
        return 'date';
    }

    init()
    {
        if (this.getConfigurations().is24Hours()) {
            this.timeFormat = 'HH:mm';
        } else {
            this.timeFormat = 'hh:mm a';
        }
        this.durationFormat = this.getConfiguration('formatDuration');
        this.dateFormat = this.getConfiguration('formatDate');
    }

    /**
     * @see https://moment.github.io/luxon/#/formatting?id=table-of-tokens
     * @param {string} format
     * @returns {string}
     * @private
     */
    _parseFormat(format)
    {
        format = format.replace('DD', 'dd');
        format = format.replace('D', 'd');
        format = format.replace('MM', 'LL');
        format = format.replace('M', 'L');
        format = format.replace('YYYY', 'yyyy');
        format = format.replace('YY', 'yy');
        format = format.replace('A', 'a');

        return format;
    }

    /**
     * @param {string} format
     * @param {string|Date|null|undefined} dateTime
     * @returns {string}
     */
    format(format, dateTime)
    {
        let newDate = null;

        if (dateTime === null || dateTime === undefined) {
            newDate = DateTime.now();
        } else if (dateTime instanceof Date) {
            newDate = DateTime.fromJSDate(dateTime);
        } else {
            newDate = DateTime.fromISO(dateTime);
        }

        // using locale english here prevents that that AM/PM is translated to the
        // locale variant: e.g. "ko" translates it to 오후 / 오전
        return newDate.toFormat(this._parseFormat(format), { locale: 'en-us' });
    }

    /**
     * @param {string|Date} dateTime
     * @returns {string}
     */
    getFormattedDate(dateTime)
    {
        return this.format(this._parseFormat(this.dateFormat), dateTime);
    }

    /**
     * Returns a "YYYY-MM-DDTHH:mm:ss" formatted string in local time.
     * This can take Date objects (e.g. from FullCalendar) and turn them into the correct format.
     *
     * @param {Date|DateTime} date
     * @param {boolean|undefined} isUtc
     * @return {string}
     */
    formatForAPI(date, isUtc = false)
    {
        if (date instanceof Date) {
            date = DateTime.fromJSDate(date);
        }

        if (isUtc === undefined || !isUtc) {
            date = date.toUTC();
        }

        return date.toISO({ includeOffset: false, suppressMilliseconds: true });
    }

    /**
     * @param {string} date
     * @param {string} format
     * @return {DateTime}
     */
    fromFormat(date, format)
    {
        // using locale en-us here prevents that Luxon expects the localized
        // version of AM/PM (e.g. 오후 / 오전 for locale "ko")
        return DateTime.fromFormat(date, this._parseFormat(format), { locale: 'en-us' });
    }

    /**
     * @param {string|null} date
     * @param {string|null} time
     * @return {DateTime}
     */
    fromHtml5Input(date, time)
    {
        date = date ?? '';
        time = time ?? '';

        if (date === '' && time === '') {
            return DateTime.invalid('Empty date and time given');
        }

        if (date !== '' && time !== '') {
            date = date + 'T' + time;
        }

        return DateTime.fromISO(date);
    }

    /**
     * @param {string} date
     * @param {string} format
     * @return {boolean}
     */
    isValidDateTime(date, format)
    {
        return this.fromFormat(date, format).isValid;
    }

    /**
     * Adds a string like "00:30:00" or "01:15" to a given date.
     *
     * @param {Date} date
     * @param {string} duration
     * @return {Date}
     */
    addHumanDuration(date, duration)
    {
        /** @type {DateTime} newDate */
        let newDate = null;

        if (date instanceof Date) {
            newDate = DateTime.fromJSDate(date);
        } else if (date instanceof DateTime) {
            newDate = date;
        } else {
            throw 'addHumanDuration() needs a JS Date';
        }

        const parsed = DateTime.fromISO(duration);
        const today = DateTime.now().startOf('day');
        const timeOfDay = parsed.diff(today);

        return newDate.plus(timeOfDay).toJSDate();
    }

    /**
     * @param {string|integer|null} since
     * @return {string}
     */
    formatDuration(since)
    {
        let duration = null;

        if (typeof since === 'string') {
            duration = DateTime.now().diff(DateTime.fromISO(since));
        } else {
            duration = Duration.fromISO('PT' + (since === null ? 0 : since) + 'S');
        }

        return this.formatLuxonDuration(duration);
    }

    /**
     * @param {integer} seconds
     * @return {string}
     */
    formatSeconds(seconds)
    {
        return this.formatLuxonDuration(Duration.fromObject({seconds: seconds}));
    }

    /**
     * @param {Duration} duration
     * @returns {string}
     * @private
     */
    formatLuxonDuration(duration)
    {
        duration = duration.shiftTo('hours', 'minutes', 'seconds');

        return this.formatAsDuration(duration.hours, duration.minutes);
    }

    /**
     * @param {Date} date
     * @param {boolean|undefined} isUtc
     * @return {string}
     */
    formatTime(date, isUtc = false)
    {
        let newDate = DateTime.fromJSDate(date);

        if (isUtc === undefined || !isUtc) {
            newDate = newDate.toUTC();
        }

        // .utc() is required for calendar
        return newDate.toFormat(this.timeFormat);
    }

    /**
     * @param {int} hours
     * @param {int} minutes
     * @return {string}
     */
    formatAsDuration(hours, minutes)
    {
        let format = this.durationFormat;

        if (hours < 0 || minutes < 0) {
            hours = Math.abs(hours);
            minutes = Math.abs(minutes);
            format = '-' + format;
        }

        return format.replace('%h', hours.toString()).replace('%m', ('0' + minutes).slice(-2));
    }

    /**
     * @param {string} duration
     * @returns {int}
     */
    getSecondsFromDurationString(duration)
    {
        const luxonDuration = this.parseDuration(duration);

        if (luxonDuration === null || !luxonDuration.isValid) {
            return 0;
        }

        return luxonDuration.as('seconds');
    }

    /**
     * @param {string} duration
     * @returns {Duration}
     */
    parseDuration(duration)
    {
        if (duration === undefined || duration === null || duration === '') {
            return new Duration({seconds: 0});
        }

        duration = duration.trim().toUpperCase();
        let luxonDuration = null;

        if (duration.indexOf(':') !== -1) {
            const [, hours, minutes, seconds] = duration.match(/(\d+):(\d+)(?::(\d+))*/);
            luxonDuration = Duration.fromObject({hours: hours, minutes: minutes, seconds: seconds});
        } else if (duration.indexOf('.') !== -1 || duration.indexOf(',') !== -1) {
            duration = duration.replace(/,/, '.');
            duration = (parseFloat(duration) * 3600).toString();
            luxonDuration = Duration.fromISO('PT' + duration + 'S');
        } else if (duration.indexOf('H') !== -1 || duration.indexOf('M') !== -1 || duration.indexOf('S') !== -1) {
            /* D for days does not work, because 'PT1H' but with days 'P1D' is used */
            luxonDuration = Duration.fromISO('PT' + duration);
        } else {
            let c = parseInt(duration);
            const d = parseInt(duration).toFixed();
            if (!isNaN(c) && duration === d) {
                duration = (c * 3600).toString();
                luxonDuration = Duration.fromISO('PT' + duration + 'S');
            }
        }

        if (luxonDuration === null || !luxonDuration.isValid) {
            return new Duration({seconds: 0});
        }

        // actually, the parsing above should be improved, but that works as well
        if (duration[0] === '-' && luxonDuration.valueOf() > 0) {
            return luxonDuration.negate();
        }

        return luxonDuration;
    }

}
