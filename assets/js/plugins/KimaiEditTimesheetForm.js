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
import jQuery from "jquery";
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
        if (this.begin !== undefined) {
            this.begin.removeEventListener('change', this.beginListener);
            delete this.beginListener;
            delete this.begin;
        }

        if (this.end !== undefined) {
            this.end.removeEventListener('change', this.endListener);
            delete this.endListener;
            delete this.end;
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

        this.begin = document.getElementById(this.formPrefix + '_begin');
        this.end = document.getElementById(this.formPrefix + '_end');
        this.duration = document.getElementById(this.formPrefix + '_duration');

        if (this.begin === null || this.end === null || this.duration === null) {
            return;
        }

        this.beginListener = () => this.changedBegin(this.begin.value);
        this.endListener = () => this.changedEnd(this.end.value);
        this.durationListener = () => this.changedDuration();

        this.begin.addEventListener('change', this.beginListener);
        this.end.addEventListener('change', this.endListener);
        this.duration.addEventListener('change', this.durationListener);
    }

    getDurationField()
    {
        return document.getElementById(this.formPrefix + '_duration');
    }

    /**
     * Ruleset:
     * - invalid begin => skip
     * - empty end => set end to begin (only if duration > 0 = running record)
     * - invalid end => skip
     * - calculate duration
     *
     * @param {string} value
     */
    changedBegin(value)
    {
        const format = this.end.dataset.format;
        const duration = this.getParsedDuration();

        let begin = this.getDateUtils().fromFormat(value, format);
        if (!begin.isValid) {
            this.setDurationAsString(null);
        }

        if (this.end.value === '' && duration.as('seconds') > 0) {
            this.applyDateToField(this.end, begin, format);
        }

        let end = this.getDateUtils().fromFormat(this.end.value, format);
        if (!end.isValid) {
            return;
        }

        if (Interval.fromDateTimes(begin, end).isBefore(begin)) {
            this.applyDateToField(this.end, begin.plus(duration), format);
        }

        begin = this.getDateUtils().fromFormat(value, format);
        end = this.getDateUtils().fromFormat(this.end.value, format);

        this.setDurationAsString(end.diff(begin));
    }


    /**
     * Ruleset:
     * - invalid end => skip
     * - empty begin => set begin to end
     * - invalid begin => skip
     * - calculate duration
     *
     * @param {string} value
     */
    changedEnd(value)
    {
        const format = this.begin.dataset.format;
        const duration = this.getParsedDuration();

        let end = this.getDateUtils().fromFormat(value, format);
        if (!end.isValid) {
            this.setDurationAsString(null);
        }

        if (this.begin.value === '') {
            this.applyDateToField(this.begin, end, format);
        }

        let begin = this.getDateUtils().fromFormat(this.begin.value, format);
        if (!begin.isValid) {
            return;
        }

        if (Interval.fromDateTimes(begin, end).isBefore(begin)) {
            this.applyDateToField(this.begin, end.minus(duration), format);
        }

        begin = this.getDateUtils().fromFormat(this.begin.value, format);
        end = this.getDateUtils().fromFormat(value, format);

        this.setDurationAsString(end.diff(begin));
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
            return;
        }

        const format = this.end.dataset.format;
        const begin = this.begin.value;
        const end = this.end.value;
        const seconds = duration.as('seconds');

        if (begin === '' && end === '') {
            this.applyDateToField(this.begin, DateTime.now(), format);
            this.applyDateToField(this.end, this.getDateUtils().fromFormat(begin, format).plus({seconds: seconds}), format);
        } else if (begin === '' && end !== '') {
            this.applyDateToField(this.begin, this.getDateUtils().fromFormat(end, format).minus({seconds: seconds}), format);
        } else if (begin !== '' && duration.as('seconds') > 0) {
            this.applyDateToField(this.end, this.getDateUtils().fromFormat(begin, format).plus({seconds: seconds}), format);
        }
    }

    /**
     * Writes the value of a duration object as human-readable string into the duration field
     *
     * @param {Duration} duration
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

        let hours = Math.floor(duration.as('hours'));
        if (hours < 10) {
            hours = '0' + hours;
        }

        this.getDurationField().value = hours + ':' + ('0' + duration.minutes).slice(-2);
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
     * @param {HTMLElement} field
     * @param {DateTime} dateTime
     * @param {string} format
     */
    applyDateToField(field, dateTime, format)
    {
        format = this.getDateUtils()._getFormat(format); // FIXME

        field.value = dateTime.toFormat(format);
        if (jQuery(field).data('daterangepicker') !== undefined) {
            jQuery(field).data('daterangepicker').setStartDate(dateTime);
            jQuery(field).data('daterangepicker').setEndDate(dateTime);
            // make sure that the project list is reloaded and the dates can be compared against the project end date
            document.getElementById(this.formPrefix + '_customer').dispatchEvent(new Event('change'));
        }
    }

}
