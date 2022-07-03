/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiReducedClickHandler: abstract class
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiReducedClickHandler extends KimaiPlugin {

    _addClickHandler(selector, callback) {
        document.body.addEventListener('click', (event) => {
            // event.currentTarget is ALWAYS the body

            let target = event.target;
            while (target !== null && !target.matches('body')) {
                if (target.matches(selector)) {
                    break;
                }

                // when an element within the row is clicked, that can trigger stuff itself, we don't want the event to be processed
                // don't act if a link, button or form element was clicked
                if (target.matches('a') || target.matches('button') || target.matches('input')) {
                    return;
                }

                target = target.parentNode;
            }

            // just in case an inner element is editable, then this should not be triggered
            if (target.isContentEditable || target.parentNode.isContentEditable) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            let href = target.dataset['href'];
            if (href === undefined || href === null) {
                href = target.href;
            }

            if (href === undefined || href === null || href === '') {
                return;
            }

            callback(href);
        });
    }

}
