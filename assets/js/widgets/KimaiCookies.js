/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiCookies: simple wrapper to handle cookies
 */

import Cookies from 'js-cookie';

export default class KimaiCookies {

    static set(name, values, options) {
        Cookies.set(name, values, options);
    }

    static get(name) {
        return Cookies.getJSON(name);
    }

    static remove(name) {
        Cookies.remove(name);
    }

}
