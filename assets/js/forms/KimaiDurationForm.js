/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDurationForm: parses and normalizes all duration input fields
 */

import KimaiFormPlugin from './KimaiFormPlugin';

export default class KimaiDurationForm extends KimaiFormPlugin {

    init()
    {
        this.selector = 'input.duration-input';
        this._changeListener = (event) => this._changedDuration(event);
        this._keyListener = (event) => this._changeDurationOnKeypress(event);
        this._inputListener = (event) => this._preventNegativeDuration(event);
    }

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form) // eslint-disable-line no-unused-vars
    {
        return true;
    }

    /**
     * Listeners are attached to the form (and not to the fields), so dynamically added fields are supported as well.
     *
     * @param {HTMLFormElement} form
     */
    activateForm(form)
    {
        // listen in capture phase, so all other change listeners already see the normalized value
        form.addEventListener('change', this._changeListener, true);
        form.addEventListener('keydown', this._keyListener);
        form.addEventListener('beforeinput', this._inputListener);
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        form.removeEventListener('change', this._changeListener, true);
        form.removeEventListener('keydown', this._keyListener);
        form.removeEventListener('beforeinput', this._inputListener);
    }

    /**
     * @param {EventTarget} element
     * @return {boolean}
     * @private
     */
    _isDurationField(element)
    {
        return element instanceof HTMLInputElement && element.matches(this.selector) && !element.disabled && !element.readOnly;
    }

    /**
     * Negative durations are not allowed: blocks typing, pasting and dropping a minus character.
     *
     * @param {InputEvent} event
     * @private
     */
    _preventNegativeDuration(event)
    {
        if (!this._isDurationField(event.target)) {
            return;
        }

        if (event.data !== null && event.data.includes('-')) {
            event.preventDefault();
        }
    }

    /**
     * @param {Event} event
     * @private
     */
    _changedDuration(event)
    {
        if (!this._isDurationField(event.target)) {
            return;
        }

        this._normalizeDuration(event.target);
    }

    /**
     * Rewrites the value of the given field into the H:MM format, using the fields parsing mode.
     *
     * Negative values are not allowed and will be removed.
     * Invalid values are kept and marked, so the user can fix them: the "pattern" attribute prevents form submission.
     *
     * @param {HTMLInputElement} field
     * @private
     */
    _normalizeDuration(field)
    {
        const value = field.value.trim();
        if (value === '') {
            field.classList.remove('is-invalid');
            return;
        }

        if (value !== field.value) {
            field.value = value;
        }

        if (field.validity.patternMismatch) {
            field.classList.add('is-invalid');
            return;
        }

        field.classList.remove('is-invalid');

        const seconds = this.getDateUtils().getSecondsFromDurationString(field.value, field.dataset['durationMode']);
        if (seconds < 0) {
            field.value = '';
            return;
        }

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds - (hours * 3600)) / 60);
        const formatted = hours + ':' + minutes.toString().padStart(2, '0');

        // changing the value moves the cursor to the end, so only write if required
        if (formatted !== field.value) {
            field.value = formatted;
        }
    }

    /**
     * @param {KeyboardEvent} event
     * @private
     */
    _changeDurationOnKeypress(event)
    {
        if (!this._isDurationField(event.target)) {
            return;
        }

        switch (event.key) {
            case 'ArrowUp':
            case 'ArrowDown':
            case 'PageUp':
            case 'PageDown':
            case 'Home':
            case 'End':
                this._normalizeDuration(event.target);
                break;
            default:
                return; // Ignore other keys
        }

        this._changeTimeOnKeypress(event, event.target, Number.MAX_SAFE_INTEGER);
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
     * @param {HTMLInputElement} timeField
     * @param {int} maxTime
     * @private
     */
    _changeTimeOnKeypress(event, timeField, maxTime)
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
        // trigger update of linked fields (e.g. end time in timesheet form or totals in quick-entry)
        timeField.dispatchEvent(new Event('change', {bubbles: true}));
        // Move cursor to original position if possible
        setTimeout(() => {
            timeField.setSelectionRange(cursorPos, cursorPos);
        }, 0);
    }
}
