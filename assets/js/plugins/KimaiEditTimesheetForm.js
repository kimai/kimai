/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEditTimesheetForm: responsible for the most important form in the application
 */

import KimaiPlugin from '../KimaiPlugin';
import { DateTime, Duration, Interval } from 'luxon';

export default class KimaiEditTimesheetForm extends KimaiPlugin {

    getId()
    {
        return 'edit-timesheet-form';
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        if (this.beginDate !== undefined) {
            this.beginDate.removeEventListener('change', this.beginListener);
            delete this.beginListener;
            delete this.begin;
        }

        if (this.beginTime !== undefined) {
            this.beginTime.removeEventListener('change', this.beginListener);
            delete this.beginTime;
        }

        if (this.endDate !== undefined) {
            this.endDate.removeEventListener('change', this.endListener);
            delete this.endListener;
            delete this.end;
        }

        if (this.endTime !== undefined) {
            this.endTime.removeEventListener('change', this.endListener);
            delete this.endTime;
        }

        if (this.duration !== undefined) {
            this.duration.removeEventListener('change', this.durationListener);
            delete this.durationListener;
            delete this.duration;
        }

        delete this.formPrefix;
    }

    activateForm(form)
    {
        this.formPrefix = form.name;

        this.beginDate = document.getElementById(this.formPrefix + '_begin_date');
        this.beginTime = document.getElementById(this.formPrefix + '_begin_time');
        this.endDate = document.getElementById(this.formPrefix + '_end_date');
        this.endTime = document.getElementById(this.formPrefix + '_end_time');
        this.duration = document.getElementById(this.formPrefix + '_duration');

        if (this.beginDate === null || this.endDate === null || this.beginTime === null || this.endTime === null || this.duration === null) {
            return;
        }

        this.beginListener = () => this.changedBegin();
        this.endListener = () => this.changedEnd();
        this.durationListener = () => this.changedDuration();

        this.beginDate.addEventListener('change', this.beginListener);
        this.beginTime.addEventListener('change', this.beginListener);
        this.endDate.addEventListener('change', this.endListener);
        this.endTime.addEventListener('change', this.endListener);
        this.duration.addEventListener('change', this.durationListener);
    }

    getDurationField()
    {
        return document.getElementById(this.formPrefix + '_duration');
    }

    /**
     * @returns {DateTime|null}
     * @private
     */
    _getBegin() {
        if (this.beginDate.value === '' || this.beginTime.value === '') {
            return null;
        }
        const date = this.getDateUtils().fromHtml5Input(this.beginDate.value, this.beginTime.value);
        if (date.invalid) {
            return null;
        }
        return date;
    }

    /**
     * @returns {DateTime|null}
     * @private
     */
    _getEnd() {
        if (this.endDate.value === '' || this.endTime.value === '') {
            return null;
        }
        const date = this.getDateUtils().fromHtml5Input(this.endDate.value, this.endTime.value);
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
    changedBegin()
    {
        const begin = this._getBegin();
        if (begin === null) {
            return;
        }

        const duration = this.getParsedDuration();
        const hasDuration = duration.as('seconds') > 0;
        const end = this._getEnd();

        if (end === null && hasDuration) {
            this.applyDateToField(begin.plus(duration), this.endDate, this.endTime);
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
    changedEnd()
    {
        const end = this._getEnd();
        // empty or invalid date => reset duration and stop progress
        if (end === null) {
            return;
        }

        const duration = this.getParsedDuration();
        const hasDuration = duration.as('seconds') > 0;
        const begin = this._getBegin();

        if (begin === null && hasDuration) {
            this.applyDateToField(end.minus(duration), this.beginDate, this.beginTime);
        } else {
            this._updateDuration();
        }
    }

    /**
     * @private
     */
    _updateDuration() {
        const begin = this._getBegin();
        const end = this._getEnd();
        let newDuration = null;

        if (begin !== null && end !== null) {
            newDuration = end.diff(begin);
        }

        this.setDurationAsString(newDuration);
    }

    /**
     * Ruleset:
     * - invalid duration => skip
     * - if begin and end are empty: set begin to now and end to duration
     * - if begin is empty and end is not empty: set begin to end minus duration
     * - if begin is not empty and end is empty and duration is > 0 (running records = 0): set end to begin plus duration
     */
    changedDuration()
    {
        const duration = this.getParsedDuration();
        if (!duration.isValid) {
            this.setDurationAsString(null);
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
            this.applyDateToField(newBegin, this.beginDate, this.beginTime);
            this.applyDateToField(newBegin.plus({seconds: seconds}), this.endDate, this.endTime);
        } else if (begin === null && end !== null) {
            this.applyDateToField(end.minus({seconds: seconds}), this.beginDate, this.beginTime);
        } else if (begin !== null && seconds > 0) {
            this.applyDateToField(begin.plus({seconds: seconds}), this.endDate, this.endTime);
        }
    }

    /**
     * Writes the value of a duration object as human-readable string into the duration field
     *
     * @param {Duration|null} duration
     */
    setDurationAsString(duration)
    {
        if (duration === null) {
            this.getDurationField().value = '';
            return;
        }

        if (!duration.isValid) {
            return;
        }

        const seconds = duration.as('seconds');
        if (seconds < 0) {
            this.getDurationField().value = '';
            return;
        }

        const hours = Math.floor(seconds / 3600);
        let minutes = Math.floor((seconds - (hours * 3600)) / 60);

        if (minutes < 10) {
            minutes = '0' + minutes;
        }

        this.getDurationField().value = hours + ':' + minutes;
    }

    /**
     * returns a duration object from the duration input field
     *
     * @return {Duration}
     */
    getParsedDuration()
    {
        return this.getDateUtils().parseDuration(this.getDurationField().value.toUpperCase());
    }

    /**
     * @param {DateTime|null} dateTime
     * @param {HTMLElement} dateField
     * @param {HTMLElement} timeField
     */
    applyDateToField(dateTime, dateField, timeField)
    {
        if (dateTime === null || dateTime.invalid) {
            dateField.value = '';
            timeField.value = '';
            return;
        }

        dateField.value = dateTime.toFormat('yyyy-LL-dd');
        timeField.value = dateTime.toFormat('HH:mm');
    }

}
