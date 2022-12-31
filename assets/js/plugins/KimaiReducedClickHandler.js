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

    /**
     * No _underscore naming for now, as it would be mangled otherwise
     * @param selector
     * @param callback
     */
    addClickHandler(selector, callback) {
        document.body.addEventListener('click', (event) => {
            // event.currentTarget is ALWAYS the body

            let target = event.target;
            while (target !== null) {
                const tagName = target.tagName.toUpperCase();
                if (tagName === 'BODY') {
                    return;
                }

                if (target.matches(selector)) {
                    break;
                }

                // when an element is clicked, which can trigger stuff itself, we don't want the event to be processed
                if (tagName === 'A' || tagName === 'BUTTON' || tagName === 'INPUT' || tagName === 'LABEL') {
                    return;
                }

                target = target.parentNode;
            }

            if (target === null) {
                return;
            }

            // just in case an inner element is editable, then this should not be triggered
            if (target.isContentEditable || target.parentNode.isContentEditable) {
                return;
            }

            if (!target.matches(selector)) {
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
