/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEditTimesheetForm: responsible for the most important form in the application
 */

import { DateTime } from 'luxon';
import KimaiFormPlugin from "./KimaiFormPlugin";

export default class KimaiTimesheetForm extends KimaiFormPlugin {

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form)
    {
        return (form.name === 'timesheet_edit_form' || form.name ==='timesheet_admin_edit_form' || form.name ==='timesheet_multi_user_edit_form');
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        if (!this.supportsForm(form)) {
            return;
        }

        if (this._beginDate !== undefined) {
            this._beginDate.removeEventListener('change', this._beginListener);
            delete this._beginListener;
            delete this._beginDate;
        }

        if (this._beginTime !== undefined) {
            this._beginTime.removeEventListener('change', this._beginListener);
            delete this._beginTime;
        }

        if (this._endTime !== undefined) {
            this._endTime.removeEventListener('change', this._endListener);
            delete this._endTime;
        }

        if (this._duration !== undefined) {
            this._duration.removeEventListener('change', this._durationListener);
            delete this._durationListener;
            delete this._duration;
        }

        if (this._durationToggle !== undefined && this._durationToggle !== null) {
            this._durationToggle.removeEventListener('change', this._durationToggleListener);
            delete this._durationToggleListener;
            delete this._durationToggle;
        }

        if (this._activity !== undefined) {
            this._activity.removeEventListener('create', this._activityListener);
            delete this._activityListener;
            delete this._activity;
        }

        if (this._project !== undefined) {
            delete this._project;
        }
    }

    activateForm(form)
    {
        if (!this.supportsForm(form)) {
            return;
        }

        const formPrefix = form.name;

        this._activity = document.getElementById(formPrefix + '_activity');
        this._project = document.getElementById(formPrefix + '_project');

        /** @param {CustomEvent} event */
        this._activityListener = (event) => {
            const project = this._project.value;
            /** @type {KimaiAPI} API */
            const API = this.getContainer().getPlugin('api');
            API.post(this._activity.dataset['create'], {
                name: event.detail.value,
                project: (project === '' ? null : project),
                visible: true,
            }, () => {
                this._project.dispatchEvent(new Event('change'));
            });
        };
        this._activity.addEventListener('create', this._activityListener);

        this._beginDate = document.getElementById(formPrefix + '_begin_date');
        this._beginTime = document.getElementById(formPrefix + '_begin_time');
        this._endTime = document.getElementById(formPrefix + '_end_time');
        this._duration = document.getElementById(formPrefix + '_duration');
        this._durationToggle = document.getElementById(formPrefix + '_duration_toggle');

        if (this._beginDate === null || this._beginTime === null || this._endTime === null || this._duration === null) {
            return;
        }

        this._beginListener = () => this._changedBegin();
        this._endListener = () => this._changedEnd();
        this._durationListener = () => this._changedDuration();

        this._beginDate.addEventListener('change', this._beginListener);
        this._beginTime.addEventListener('change', this._beginListener);
        this._endTime.addEventListener('change', this._endListener);
        this._duration.addEventListener('change', this._durationListener);
        this._beginTime.addEventListener('blur', () => {
            this._beginTime.value = this._formatTimeInput(this._beginTime.value, this._beginTime.dataset['format']);
            this._changedBegin();
        });

        this._endTime.addEventListener('blur', () => {
            this._endTime.value = this._formatTimeInput(this._endTime.value, this._endTime.dataset['format']);
            this._changedEnd();
        });
        if (this._duration !== null && this._durationToggle !== null) {
            this._durationToggleListener = () => {
                this._durationToggle.classList.toggle('text-success');
            };
            this._durationToggle.addEventListener('click', this._durationToggleListener);
        }
    }

    _isDurationConnected()
    {
        if (this._duration === null && this._durationToggle === null) {
            return false;
        }

        if (this._durationToggle === null) {
            return true;
        }

        return this._durationToggle.classList.contains('text-success');
    }

    /**
     * @returns {DateTime|null}
     * @private
     */
    _getBegin()
    {
        if (this._beginDate.value === '' || this._beginTime.value === '') {
            return null;
        }

        let date = this._parseBegin(this._beginTime.dataset['format']);

        if (date.invalid) {
            date = this._parseBegin(this._fixTimeFormat(this._beginTime.dataset['format']));

            if (date.invalid) {
                return null;
            }
        }

        return date;
    }

    _parseBegin(timeFormat)
    {
        return this.getDateUtils().fromFormat(
            this._beginDate.value + ' ' + this._beginTime.value,
            this._beginDate.dataset['format'] + ' ' + timeFormat,
        );
    }

    _parseEnd(endDate, timeFormat)
    {
        let date = this.getDateUtils().fromFormat(
            endDate.toFormat('yyyy-LL-dd') + ' ' + this._endTime.value,
            'yyyy-LL-dd ' + timeFormat,
        );

        if (date.invalid) {
            date = this.getDateUtils().fromFormat(
                endDate.toFormat('yyyy-LL-dd') + ' ' + this._endTime.value,
                'yyyy-LL-dd ' + this._fixTimeFormat(timeFormat),
            );
        }

        return date;
    }

    _fixTimeFormat(format)
    {
        return format.replace('HH', 'H').replace('hh', 'h');
    }

    /**
     * @returns {DateTime|null}
     * @private
     */
    _getEnd()
    {
        if (this._endTime.value === '') {
            return null;
        }

        let date = this._parseEnd(DateTime.now(), this._endTime.dataset['format']);

        const begin = this._getBegin();
        if (begin !== null) {
            date = this._parseEnd(begin, this._endTime.dataset['format']);

            if (date < begin) {
                date = date.plus({days: 1});
            }
        }

        if (date.invalid) {
            return null;
        }

        return date;
    }

    /**
     * Ruleset:
     * - invalid begin => skip
     * - empty end => set end to begin (only if duration > 0 = running record)
     * - invalid end => skip
     * - calculate duration
     */
    _changedBegin()
    {
        const begin = this._getBegin();
        if (begin === null) {
            return;
        }

        const duration = this._getParsedDuration();
        const hasDuration = duration.as('seconds') > 0;
        const end = this._getEnd();

        if (end === null && hasDuration) {
            this._applyDateToField(begin.plus(duration), null, this._endTime);
        } else {
            this._updateDuration();
        }
    }

    /**
     * Ruleset:
     * - invalid end => skip
     * - empty begin => set begin to end
     * - invalid begin => skip
     * - calculate duration
     */
    _changedEnd()
    {
        const end = this._getEnd();
        // empty or invalid date => reset duration and stop progress
        if (end === null) {
            return;
        }

        const duration = this._getParsedDuration();
        const hasDuration = duration.as('seconds') > 0;
        const begin = this._getBegin();

        if (begin === null && hasDuration) {
            this._applyDateToField(end.minus(duration), this._beginDate, this._beginTime);
        } else {
            this._updateDuration();
        }
    }

    /**
     * @private
     */
    _updateDuration()
    {
        const begin = this._getBegin();
        const end = this._getEnd();
        let newDuration = null;

        if (begin !== null && end !== null) {
            newDuration = end.diff(begin);
        }

        this._setDurationAsString(newDuration);
    }

    /**
     * Ruleset:
     * - invalid duration => skip
     * - if begin and end are empty: set begin to now and end to duration
     * - if begin is empty and end is not empty: set begin to end minus duration
     * - if begin is not empty and end is empty and duration is > 0 (running records = 0): set end to begin plus duration
     */
    _changedDuration()
    {
        if (!this._isDurationConnected()) {
            return;
        }

        const duration = this._getParsedDuration();
        if (!duration.isValid) {
            this._setDurationAsString(null);
            return;
        }

        const begin = this._getBegin();
        let end = this._getEnd();
        const seconds = duration.as('seconds');

        if (seconds < 0) {
            end = null;
        }

        if (begin === null && end === null) {
            const newBegin = DateTime.now();
            this._applyDateToField(newBegin, this._beginDate, this._beginTime);
            this._applyDateToField(newBegin.plus({seconds: seconds}), null, this._endTime);
        } else if (begin === null && end !== null) {
            this._applyDateToField(end.minus({seconds: seconds}), this._beginDate, this._beginTime);
        } else if (begin !== null && seconds >= 0) {
            this._applyDateToField(begin.plus({seconds: seconds}), null, this._endTime);
        }
    }

    /**
     * Writes the value of a duration object as human-readable string into the duration field
     *
     * @param {Duration|null} duration
     */
    _setDurationAsString(duration)
    {
        if (!this._isDurationConnected()) {
            return;
        }

        if (duration === null) {
            this._duration.value = '';
            return;
        }

        if (!duration.isValid) {
            return;
        }

        const seconds = duration.as('seconds');
        if (seconds < 0) {
            this._duration.value = '';
            return;
        }

        const hours = Math.floor(seconds / 3600);
        let minutes = Math.floor((seconds - (hours * 3600)) / 60);

        if (minutes < 10) {
            minutes = '0' + minutes;
        }

        this._duration.value = hours + ':' + minutes;
    }

    /**
     * Returns a duration object from the duration input field.
     *
     * @private
     * @return {Duration}
     */
    _getParsedDuration()
    {
        return this.getDateUtils().parseDuration(this._duration.value.toUpperCase());
    }

    /**
     * @param {DateTime|null} dateTime
     * @param {HTMLElement|null} dateField
     * @param {HTMLElement} timeField
     * @private
     */
    _applyDateToField(dateTime, dateField, timeField)
    {
        if (dateTime === null || dateTime.invalid) {
            dateField.value = '';
            timeField.value = '';
            return;
        }

        if (dateField !== null) {
            dateField.value = this.getDateUtils().format(dateField.dataset['format'], dateTime);
        }
        timeField.value = this.getDateUtils().format(timeField.dataset['format'], dateTime);
    }

    /**
     * Formats raw user input into a time string matching the expected format.
     *
     * Ruleset:
     * - if input already contains a colon and AM/PM, return unchanged
     * - if input matches compact 12-hour format (e.g., "845am", "1245 pm"), convert to "h:mm AM/PM"
     * - if input is numeric-only (e.g., "545", "1645"):
     *   - if 12-hour format is expected, convert to "h:mm AM/PM"
     *   - if 24-hour format is expected, convert to "HH:mm"
     * - if input is invalid or cannot be parsed, return unchanged
     *
     * @param {string} input   Raw user-entered time string
     * @param {string} format  Expected output format (e.g., "h:mm A" or "HH:mm")
     * @returns {string}       Formatted time string or original input
     */
    _formatTimeInput(input, format)
    {
        const trimmed = input.trim();

        if (/[ap]m/i.test(trimmed) && trimmed.includes(':')) {
            return trimmed;
        }

        const twelveHour = this.parseCompact12HourTime(trimmed);
        if (twelveHour !== null) {
            return twelveHour;
        }

        const twentyFourHour = this.parseCompact24HourTime(trimmed, format);
        if (twentyFourHour !== null) {
            return twentyFourHour;
        }

        return trimmed;
    }

    /**
     * Parses compact 12-hour input like "845am" into a formatted string like "8:45 AM".
     * @private
     */
    parseCompact12HourTime(value) {
        const match = value.match(/^(\d{3,4})\s*(am|pm)$/i);
        if (!match) {
            return null;
        }

        const digits = match[1];
        const suffix = match[2].toUpperCase();

        const hours = parseInt(digits.slice(0, -2), 10);
        const minutes = parseInt(digits.slice(-2), 10);

        if (isNaN(hours) || isNaN(minutes) || hours < 1 || hours > 12 || minutes > 59) {
            return null;
        }

        return `${hours}:${String(minutes).padStart(2, '0')} ${suffix}`;
    }

    /**
     * Parses numeric-only time like "1645" into 12h or 24h format.
     * @private
     */
    parseCompact24HourTime(value, format) {
        const digits = value.replace(/\D/g, '');
        if (!/^\d{3,4}$/.test(digits)) {
            return null;
        }

        let hours = parseInt(digits.slice(0, -2), 10);
        const minutes = parseInt(digits.slice(-2), 10);

        if (isNaN(hours) || isNaN(minutes) || hours > 23 || minutes > 59) {
            return null;
        }

        const is12Hour = format.includes('a') || format.includes('A');
        if (is12Hour) {
            const suffix = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return `${hours}:${String(minutes).padStart(2, '0')} ${suffix}`;
        }

        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }
}
