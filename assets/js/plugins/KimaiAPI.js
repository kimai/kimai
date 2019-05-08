/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAPI: easy access to API methods
 */

import jQuery from 'jquery';
import KimaiPlugin from "../KimaiPlugin";

export default class KimaiAPI extends KimaiPlugin {

    getId() {
        return 'api';
    }

    get(url, callback) {
        jQuery.ajax({
            url: url,
            headers: {
                'X-AUTH-SESSION': true,
                'Content-Type':'application/json'
            },
            method: 'GET',
            dataType: 'json',
            success: callback
        });
    }

    patch(url, callback) {
        jQuery.ajax({
            url: url,
            headers: {
                'X-AUTH-SESSION': true,
                'Content-Type':'application/json'
            },
            method: 'PATCH',
            dataType: 'json',
            success: callback
        });
    }

}
