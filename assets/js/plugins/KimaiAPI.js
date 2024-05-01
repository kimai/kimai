/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAPI: easy access to API methods
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiAPI extends KimaiPlugin {

    getId() {
        return 'api';
    }

    _headers() {
        const headers = new Headers();
        headers.append('Content-Type', 'application/json');

        return headers;
    }

    get(url, data, callbackSuccess, callbackError) {
        if (data !== undefined) {
            const params = (new URLSearchParams(data)).toString();
            if (params !== '') {
                url = url + (url.includes('?') ? '&' : '?') + params;
            }
        }

        if (callbackError === undefined) {
            callbackError = (error) => {
                this.handleError('An error occurred', error);
            };
        }

        this.fetch(url, {
            method: 'GET',
            headers: this._headers()
        }).then((response) => {
            response.json().then((json) => {
                callbackSuccess(json);
            });
        }).catch((error) => {
            callbackError(error);
        });
    }

    post(url, data, callbackSuccess, callbackError) {
        if (callbackError === undefined) {
            callbackError = (error) => {
                this.handleError('action.update.error', error);
            };
        }

        this.fetch(url, {
            method: 'POST',
            body: this._parseData(data),
            headers: this._headers()
        }).then((response) => {
            response.json().then((json) => {
                callbackSuccess(json);
            });
        }).catch((error) => {
            callbackError(error);
        });
    }

    patch(url, data, callbackSuccess, callbackError) {
        if (callbackError === undefined) {
            callbackError = (error) => {
                this.handleError('action.update.error', error);
            };
        }

        this.fetch(url, {
            method: 'PATCH',
            body: this._parseData(data),
            headers: this._headers()
        }).then((response) => {
            if (response.statusCode === 204) {
                callbackSuccess();
            } else {
                response.json().then((json) => {
                    callbackSuccess(json);
                });
            }
        }).catch((error) => {
            callbackError(error);
        });
    }

    delete(url, callbackSuccess, callbackError) {
        if (callbackError === undefined) {
            callbackError = (error) => {
                this.handleError('action.delete.error', error);
            };
        }

        this.fetch(url, {
            method: 'DELETE',
            headers: this._headers()
        }).then(() => {
            callbackSuccess();
        }).catch((error) => {
            callbackError(error);
        });
    }

    /**
     * @param {string|object} data
     * @returns {string}
     * @private
     */
    _parseData(data) {
        if (typeof data === 'object') {
            return JSON.stringify(data);
        }

        return data;
    }

    /**
     * @param {string} message
     * @param {Response} response
     */
    handleError(message, response) {
        if (response.headers === undefined) {
            // this can happen if someone clicks to fast and auto running
            // requests (e.g. active records) are aborted
            return;
        }

        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            response.json().then(data => {
                let resultError = data.message;
                // find validation errors
                if (response.status === 400 && data.errors) {
                    let collected = ['<u>' + resultError + '</u>'];
                    // form errors that are not attached to a field (like extra fields)
                    if (data.errors.errors) {
                        for (let error of data.errors.errors) {
                            collected.push(error);
                        }
                    }
                    if (data.errors.children) {
                        for (let field in data.errors.children) {
                            let tmpField = data.errors.children[field];
                            if (tmpField.errors !== undefined && tmpField.errors.length > 0) {
                                for (let error of tmpField.errors) {
                                    collected.push(error);
                                }
                            }
                        }
                    }
                    if (collected.length > 0) {
                        resultError = collected;
                    }
                }

                this.getPlugin('alert').error(message, resultError);

            });
        } else {
            response.text().then(() => {
                const resultError = '[' + response.statusCode + '] ' + response.statusText;
                this.getPlugin('alert').error(message, resultError);
            });
        }
    }

}
