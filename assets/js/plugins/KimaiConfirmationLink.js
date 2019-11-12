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

                    // is this a link? 
                    let url = attributes['href'];
                    // or another HTML element with a custom href 
                    if (!url) {
                        url = target.getAttribute('href');
                    }
                    
                    // or is this a button?
                    let form = null;
                    if (target.type === 'submit' && target.form !== undefined) {
                        form = target.form;
                    }

                    if (attributes.question !== undefined) {
                        self.getContainer().getPlugin('alert').question(attributes.question, function(value) {
                            if (value) {
                                if (form === null) {
                                    document.location = url;
                                } else {
                                    if (url !== null) {
                                        form.action = url;
                                    }
                                    form.submit();
                                }
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
