/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiLocaleDemo: live update of the formatting examples below the language selector in the user preferences
 */

import KimaiPlugin from '../KimaiPlugin';
import { DateTime } from 'luxon';

export default class KimaiLocaleDemo extends KimaiPlugin {

    init()
    {
        this._root = document.querySelector('[data-locale-demo]');
        if (this._root === null || this._root.dataset['select'] === undefined) {
            return;
        }

        this._select = document.getElementById(this._root.dataset['select']);
        if (this._select === null) {
            return;
        }

        this._formats = JSON.parse(this._root.dataset['formats']);
        this._date = DateTime.fromISO(this._root.dataset['date']).toJSDate();
        this._duration = parseInt(this._root.dataset['duration']);
        this._money = parseFloat(this._root.dataset['money']);
        this._currency = this._root.dataset['currency'];
        this._yes = this._root.dataset['yes'];
        this._no = this._root.dataset['no'];

        this._select.addEventListener('change', () => {
            try {
                this._updateDemoValues();
            } catch {
                // exotic locale patterns must never break the page, the values just stay as they were
            }
        });
    }

    /**
     * Updates all demo values based on the currently selected locale.
     *
     * @private
     */
    _updateDemoValues()
    {
        const locale = this._select.value;
        const formats = this._formats[locale];
        if (formats === undefined) {
            return;
        }

        const DATE = this.getDateUtils();
        const localeTag = locale.replace('_', '-');
        const decimalFormat = new Intl.NumberFormat(localeTag, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const moneyFormat = new Intl.NumberFormat(localeTag, {style: 'currency', currency: this._currency});

        const values = {
            'date': DATE.format(formats.date, this._date),
            'time': DATE.format(formats.time, this._date),
            'duration': DATE.formatSeconds(this._duration),
            'decimal': decimalFormat.format(this._duration / 3600),
            'money': moneyFormat.format(this._money)
        };

        for (const name in values) {
            this._setValue(name, values[name]);
        }

        this._updateRtl(formats.rtl);
    }

    /**
     * @param {string} name
     * @param {string} value
     * @private
     */
    _setValue(name, value)
    {
        const entry = this._root.querySelector('[data-demo-value="' + name + '"] .locale-demo-value');
        if (entry !== null) {
            entry.textContent = value;
        }
    }

    /**
     * @param {boolean} isRtl
     * @private
     */
    _updateRtl(isRtl)
    {
        const badge = this._root.querySelector('[data-demo-value="rtl"] .locale-demo-value .badge');
        if (badge === null) {
            return;
        }

        badge.textContent = isRtl ? this._yes : this._no;
        badge.className = isRtl ? 'badge bg-success text-success-fg' : 'badge bg-default text-default-fg';
    }
}
