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

    get(url, data, callbackSuccess, callbackError) {
        jQuery.ajax({
            url: url,
            headers: {
                'X-AUTH-SESSION': true,
                'Content-Type':'application/json'
            },
            method: 'GET',
            data: data,
            dataType: 'json',
            success: callbackSuccess,
            error: callbackError
        });
    }

    post(url, data, callbackSuccess, callbackError) {
        jQuery.ajax({
            url: url,
            headers: {
                'X-AUTH-SESSION': true,
                'Content-Type':'application/json'
            },
            method: 'POST',
            data: data,
            dataType: 'json',
            success: callbackSuccess,
            error: callbackError
        });
    }

    patch(url, data, callbackSuccess, callbackError) {
        jQuery.ajax({
            url: url,
            headers: {
                'X-AUTH-SESSION': true,
                'Content-Type':'application/json'
            },
            method: 'PATCH',
            data: data,
            dataType: 'json',
            success: callbackSuccess,
            error: callbackError
        });
    }

    delete(url, callbackSuccess, callbackError) {
        jQuery.ajax({
            url: url,
            headers: {
                'X-AUTH-SESSION': true,
                'Content-Type':'application/json'
            },
            method: 'DELETE',
            dataType: 'json',
            success: callbackSuccess,
            error: callbackError
        });
    }

}
