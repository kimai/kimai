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
        if (callbackError === null || callbackError === undefined) {
            callbackError = this.getPostErrorHandler();
        }

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
        if (callbackError === null || callbackError === undefined) {
            callbackError = this.getPatchErrorHandler();
        }

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
        if (callbackError === null || callbackError === undefined) {
            callbackError = this.getDeleteErrorHandler();
        }

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

    getDeleteErrorHandler() {
        const self = this;
        return function(xhr, err) {
            self.handleError('action.delete.error', xhr, err);
        };
    }

    getPatchErrorHandler() {
        const self = this;
        return function(xhr, err) {
            self.handleError('action.update.error', xhr, err);
        };
    }

    getPostErrorHandler() {
        const self = this;
        return function(xhr, err) {
            self.handleError('action.update.error', xhr, err);
        };
    }

    /**
     * @param {string} message
     * @param {jqXHR} xhr
     * @param {string} err
     */
    handleError(message, xhr, err) {
        let resultError = err;
        if (xhr.responseJSON && xhr.responseJSON.message) {
            resultError = xhr.responseJSON.message;
            // find validation errors
            if (xhr.status === 400 && xhr.responseJSON.errors && xhr.responseJSON.errors.children) {
                let collected = ['<u>' + resultError + '</u>'];
                for (let field in xhr.responseJSON.errors.children) {
                    let tmpField = xhr.responseJSON.errors.children[field];
                    if (tmpField.hasOwnProperty('errors') && tmpField.errors.length > 0) {
                        for (let error of tmpField.errors) {
                            collected.push(error);
                        }
                    }
                }
                if (collected.length > 0) {
                    resultError = collected;
                }
            }
        } else if (xhr.status && xhr.statusText) {
            resultError = '[' + xhr.status + '] ' + xhr.statusText;
        }

        this.getPlugin('alert').error(message, resultError);
    }

}
