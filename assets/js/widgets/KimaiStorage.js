/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiStorage: simple wrapper to handle localStorage access
 */

export default class KimaiStorage {

    static set(name, values) {
        window.localStorage.setItem(name, JSON.stringify(values));
    }

    static get(name) {
        let value = window.localStorage.getItem(name);
        if (value === undefined || value === null) {
            return null;
        }
        return JSON.parse(value);
    }

    static remove(name) {
        window.localStorage.removeItem(name);
    }

}
