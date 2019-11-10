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
 * Allows to assign the given selector to any element, which then is used as click-handler
 * calling an API method and trigger the event from data-event attribute afterwards.
 *
 * @param selector
 */
export default class KimaiConfirmationLink extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        const self = this;
        document.addEventListener('click', function(event) {
            let target = event.target;
            while (target !== null && !target.matches('body')) {
                if (target.classList.contains(self.selector)) {
                    const attributes = target.dataset;

                    let url = attributes['href'];
                    if (!url) {
                        url = target.getAttribute('href');
                    }

                    if (attributes.question !== undefined) {
                        self.getContainer().getPlugin('alert').question(attributes.question, function(value) {
                            if (value) {
                                document.location = url;
                            }
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
