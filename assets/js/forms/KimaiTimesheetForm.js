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
            this._duration.removeEventListener('keydown', this._durationKeyListener);
            delete this._durationKeyListener;
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
        this._durationKeyListener = (event) => this._changeDurationOnKeypress(event);

        this._beginDate.addEventListener('change', this._beginListener);
        this._beginTime.addEventListener('change', this._beginListener);
        this._endTime.addEventListener('change', this._endListener);
        this._duration.addEventListener('change', this._durationListener);
        this._duration.addEventListener('keydown', this._durationKeyListener);

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
            this._addSecondsToEndDate(newBegin, seconds);
        } else if (begin === null && end !== null) {
            this._applyDateToField(end.minus({seconds: seconds}), this._beginDate, this._beginTime);
        } else if (begin !== null && seconds >= 0) {
            this._addSecondsToEndDate(begin, seconds);
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
        return this.getDateUtils().parseDuration(this._duration.value);
    }

    /**
     * @param {DateTime} dateTime
     * @param {int} seconds
     * @private
     */
    _addSecondsToEndDate(dateTime, seconds)
    {
        // if the duration is longer than one day, the end field should be empty
        // so kimai can calculate it after submitting the data from start + duration
        if (seconds < 86400) {
            this._applyDateToField(dateTime.plus({seconds: seconds}), null, this._endTime);
        } else {
            this._endTime.value = '';
        }
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
     * @param {KeyboardEvent} event
     * @private
     */
    _changeDurationOnKeypress(event)
    {
        switch (event.key) {
            case 'ArrowUp':
            case 'ArrowDown':
            case 'PageUp':
            case 'PageDown':
            case 'Home':
            case 'End':
                this._setDurationAsString(this._getParsedDuration());
                break;
            default:
                return; // Ignore other keys
        }

        this._changeTimeOnKeypress(event, this._duration, 99999, this._durationListener);
    }

    /**
     * This method helps the user to change a duration field with simple keyboard interaction:
     * - Read the current duration from the given timeField input in format HH:MM (no seconds)
     * - Change the duration based on the rules below
     * - Write the new duration back to the field
     * - If the field is empty or invalid it uses 00:00 as start-time
     * - Duration cannot exceed maxtime (which is given in minutes)
     * - Duration cannot drop below 00:00
     * - Read the position of the cursor and decide whether to increase minutes or hours: if the cursor is in the hour section (before the colon) change hours, if the cursor is in the minute section (after the colon) change minutes
     * - It reads the pressed key from the given KeyboardEvent and changes the duration accordingly to the rules below
     *
     * Rules to apply when a key is pressed:
     * - ArrowUp key to increase the duration (either 5 minutes or 1 hour, depending on the cursor position)
     * - ArrowDown key to decrease the duration (either 5 minutes or 1 hour, depending on the cursor position)
     * - PageUp key to increase the duration by 1 hour
     * - PageDown key to decrease the duration by 1 hour
     * - Home key to set the duration to 08:00
     * - End key to set the duration to 00:00
     * - all other keys are ignored
     *
     * @param {KeyboardEvent} event
     * @param {HTMLElement} timeField
     * @param {int} maxTime
     * @param {function} changeCallback
     * @private
     */
    _changeTimeOnKeypress(event, timeField, maxTime, changeCallback)
    {
        // Parse current value or default to 00:00
        let value = timeField.value || '00:00';
        let [hours, minutes] = value.split(':').map(Number);
        if (isNaN(hours)) { hours = 0; }
        if (isNaN(minutes)) { minutes = 0; }

        // Cursor position: before or after colon
        const cursorPos = timeField.selectionStart || 0;
        const colonPos = value.indexOf(':');
        const inHour = cursorPos <= colonPos;

        // Helper to clamp values
        const clamp = (h, m) => {
            let total = h * 60 + m;
            if (total < 0) { total = 0; }
            if (total > maxTime) { total = maxTime; }
            h = Math.floor(total / 60);
            m = total % 60;
            return [h, m];
        };

        switch (event.key) {
            case 'ArrowUp':
                if (inHour) {
                    [hours, minutes] = clamp(hours + 1, minutes);
                } else {
                    [hours, minutes] = clamp(hours, minutes + 5);
                }
                break;
            case 'ArrowDown':
                if (inHour) {
                    [hours, minutes] = clamp(hours - 1, minutes);
                } else {
                    [hours, minutes] = clamp(hours, minutes - 5);
                }
                break;
            case 'PageUp':
                [hours, minutes] = clamp(hours + 1, minutes);
                event.preventDefault();
                break;
            case 'PageDown':
                [hours, minutes] = clamp(hours - 1, minutes);
                event.preventDefault();
                break;
            case 'Home':
                // TODO this should use the configured working time for today
                hours = 8;
                minutes = 0;
                event.preventDefault();
                break;
            case 'End':
                hours = 0;
                minutes = 0;
                event.preventDefault();
                break;
            default:
                return; // Ignore other keys
        }

        // Format and set value
        timeField.value = `${hours}:${minutes.toString().padStart(2, '0')}`;
        // trigger update of linked fields
        changeCallback(timeField);
        // Move cursor to original position if possible
        setTimeout(() => {
            timeField.setSelectionRange(cursorPos, cursorPos);
        }, 0);
    }
}
