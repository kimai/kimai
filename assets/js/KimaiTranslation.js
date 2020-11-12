/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiTranslation: handling translation strings
 */

export default class KimaiTranslation {

    /**
     * @param {Array<string, string>} translations
     */
    constructor(translations) {
        this._translations = translations;
    }

    /**
     * @param {string} name
     * @returns {string}
     */
    get(name) {
        return this._translations[name];
    }

    /**
     * @param {string} name
     * @returns {boolean}
     */
    has(name) {
        return name in this._translations;
    }

}
