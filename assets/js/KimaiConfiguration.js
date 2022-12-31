/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiConfiguration: handling all configuration and runtime settings
 */

export default class KimaiConfiguration {

    constructor(configurations) {
        this._configurations = configurations;
    }

    get(name) {
        return this._configurations[name];
    }

    has(name) {
        return name in this._configurations;
    }

    isRTL() {
        return this.get('direction') === 'rtl';
    }

    getLanguage() {
        return this.get('locale').replace('_', '-');
    }

    is24Hours() {
        return !!this.get('twentyFourHours');
    }

    /**
     * @param {boolean} iso
     * @return {number}
     */
    getFirstDayOfWeek(iso = true) {
        if (iso === undefined) {
            iso = true;
        }
        let config = this.get('first_dow_iso');
        if (!iso) {
            config = config % 7;
        }
        return config;
    }

}
