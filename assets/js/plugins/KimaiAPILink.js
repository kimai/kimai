/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAPILink
 *
 * allows to assign the given selector to any element, which then is used as click-handler
 * calling an API method and trigger the event from data-event attribute afterwards
 */

import KimaiClickHandlerReducedInTableRow from "./KimaiClickHandlerReducedInTableRow";

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
                if (target.matches(self.selector)) {
                    const attributes = target.dataset;

                    let url = attributes['href'];
                    if (!url) {
                        url = target.getAttribute('href');
                    }

                    const method = attributes['method'];
                    const eventName = attributes['event'];
                    const api = self.getContainer().getPlugin('api');
                    const eventing = self.getContainer().getPlugin('event');

                    if (method === 'PATCH') {
                        api.patch(url, function(result) {
                            eventing.trigger(eventName);
                        });
                    }

                    event.preventDefault();
                    event.stopPropagation();
                }

                target = target.parentNode;
            }
        });
    }

}
