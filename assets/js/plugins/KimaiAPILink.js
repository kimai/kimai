/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import KimaiClickHandlerReducedInTableRow from "./KimaiClickHandlerReducedInTableRow";

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
export default class KimaiAPILink extends KimaiClickHandlerReducedInTableRow {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        const self = this;
        document.addEventListener('click', function(event) {
            let target = event.target;
            while (!target.matches('body')) {
                if (target.classList.contains(self.selector)) {
                    const attributes = target.dataset;

                    let url = attributes['href'];
                    if (!url) {
                        url = target.getAttribute('href');
                    }

                    if (attributes.question !== undefined) {
                        self.getContainer().getPlugin('alert').question(attributes.question, function() {
                            self._callApi(url, attributes);
                        });
                    } else {
                        self._callApi(url, attributes);
                    }

                    event.preventDefault();
                    event.stopPropagation();
                }

                target = target.parentNode;
            }
        });
    }

    _callApi(url, attributes)
    {
        const method = attributes['method'];
        const eventName = attributes['event'];
        const api = this.getContainer().getPlugin('api');
        const eventing = this.getContainer().getPlugin('event');
        const alert = this.getContainer().getPlugin('alert');
        const successHandle = function(result) {
            eventing.trigger(eventName);
            if (attributes.msgSuccess) {
                alert.success(attributes.msgSuccess);
            }
        };
        const errorHandle = function(xhr, err) {
            let message = 'action.update.error';
            if (attributes.msgError) {
                message = attributes.msgError;
            }
            if (xhr.responseJSON && xhr.responseJSON.message) {
                err = xhr.responseJSON.message;
            }
            alert.error(message, err);
        };

        if (method === 'PATCH') {
            api.patch(url, successHandle, errorHandle);
        } else if (method === 'DELETE') {
            api.delete(url, successHandle, errorHandle);
        }
    }

}
