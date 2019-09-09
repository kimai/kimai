/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiClickHandlerReducedInTableRow: abstract class
 */

import jQuery from 'jquery';
import KimaiPlugin from "../KimaiPlugin";

export default class KimaiClickHandlerReducedInTableRow extends KimaiPlugin {

    _addClickHandlerReducedInTableRow(selector, callback)  {
        jQuery('body').on('click', selector, function(event) {
            // just in case an inner element is editable, than this should not be triggered
            if (event.target.parentNode.isContentEditable || event.target.isContentEditable) {
                return;
            }

            // handles the "click" on table rows to open an entry for editing 
            let target = event.target;
            if (event.currentTarget.matches('tr')) {
                while (target !== null && !target.matches('body')) {
                    // when an element within the row is clicked, that can trigger stuff itself, we don't want the event to be processed
                    // don't act if a link, button or form element was clicked
                    if (target.matches('a') || target.matches ('button') || target.matches ('input')) {
                        return;
                    }
                    target = target.parentNode;
                }
            }

            event.preventDefault();
            event.stopPropagation();

            let href = jQuery(this).attr('data-href');
            if (!href) {
                href = jQuery(this).attr('href');
            }

            callback(href);
        });
    }

}
