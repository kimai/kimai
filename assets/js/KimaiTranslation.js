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

    constructor(translations) {
        this._translations = translations;
    }

    get(name) {
        return this._translations[name];
    }

    has(name) {
        return name in this._translations;
    }

}
