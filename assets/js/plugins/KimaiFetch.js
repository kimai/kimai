/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiEscape: sanitize strings
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiFetch extends KimaiPlugin {

    getId() {
        return 'fetch';
    }

    /**
     * @param {string} url
     * @param {object} options
     * @returns {Promise<Response>}
     */
    fetch(url, options = {}) {
        if (options.headers === undefined) {
            options.headers = new Headers();
        }
        options.headers.append('X-Requested-With', 'Kimai');

        options = {...{
            redirect: 'follow',
        }, ...options};

        return new Promise((resolve, reject) => {
            fetch(url, options).then(response => {
                if (response.ok) {
                    if (response.status === 201 && response.headers.has('x-modal-redirect')) {
                        window.location = response.headers.get('x-modal-redirect');
                        return;
                    }

                    // "ok" is only in status code range of 2xx
                    resolve(response);
                    return;
                }

                let stopPropagation = false;
                switch (response.status) {
                    case 403: {
                        if (response.headers.has('login-required')) {
                            const loginUrl = this.getConfiguration('login').toString();
                            /** @type {KimaiAlert} alert */
                            const alert = this.getContainer().getPlugin('alert');
                            alert.question(this.translate('login.required'), (result) => {
                                if (result === true) {
                                    window.location.replace(loginUrl);
                                }
                            });
                            stopPropagation = true;
                        }
                        break;
                    }
                    default: {
                        console.log('Some error occurred');
                        break;
                    }
                }

                if (!stopPropagation) {
                    reject(response);
                }
            })
            .catch(error => {
                console.log('Error occurred while talking to Kimai backend', error);
                reject(error);
            });
        });
    }
}
