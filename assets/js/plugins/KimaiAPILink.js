/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import KimaiPlugin from "../KimaiPlugin";

/**
 * Needs to be initialized with a class name.
 *
 * A link like <a href=# class=remoteLink> can be activated with:
 * new KimaiAPILink('remoteLink')
 *
 * Allows to assign the given selector to any element, which then is used as click-handler
 * calling an API method and trigger the event from data-event attribute afterwards.
 *
 * @param selector
 */
export default class KimaiAPILink extends KimaiPlugin {

    constructor(selector) {
        super();
        this._selector = selector;
    }

    init() {
        document.addEventListener('click', (event) => {
            let target = event.target;
            while (target !== null && typeof target.matches === "function" && !target.matches('body')) {
                if (target.classList.contains(this._selector)) {
                    const attributes = target.dataset;

                    let url = attributes['href'];
                    if (!url) {
                        url = target.getAttribute('href');
                    }

                    if (attributes.question !== undefined) {
                        this.getContainer().getPlugin('alert').question(attributes.question, (value) => {
                            if (value) {
                                this._callApi(url, attributes);
                            }
                        });
                    } else {
                        this._callApi(url, attributes);
                    }

                    event.preventDefault();
                    event.stopPropagation();
                }

                target = target.parentNode;
            }
        });
    }

    /**
     * @param {string} url
     * @param {DOMStringMap} attributes
     * @private
     */
    _callApi(url, attributes)
    {
        const method = attributes['method'];
        const eventName = attributes['event'];
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');
        /** @type {KimaiEvent} EVENTS */
        const EVENTS = this.getContainer().getPlugin('event');
        /** @type {KimaiAlert} ALERT */
        const ALERT = this.getContainer().getPlugin('alert');
        const successHandle = () => {
            EVENTS.trigger(eventName);
            if (attributes['msgSuccess'] !== undefined) {
                ALERT.success(attributes['msgSuccess']);
            }
        };
        const errorHandle = (error) => {
            let message = 'action.update.error';
            if (attributes['msgError'] !== undefined) {
                message = attributes['msgError'];
            }
            API.handleError(message, error);
        };

        let data = {};
        if (attributes['payload'] !== undefined) {
            data = attributes['payload'];
        }

        if (method === 'PATCH') {
            API.patch(url, data, successHandle, errorHandle);
        } else if (method === 'POST') {
            let data = {};
            API.post(url, data, successHandle, errorHandle);
        } else if (method === 'DELETE') {
            API.delete(url, successHandle, errorHandle);
        } else if (method === 'GET') {
            API.get(url, data, successHandle, errorHandle);
        }
    }

}
