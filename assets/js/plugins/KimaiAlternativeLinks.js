/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAlternativeLinks
 *
 * allows to assign the given selector to any element, which then is used as click-handler
 * redirecting to the URL given in the elements 'data-href' or 'href' attribute
 */

import jQuery from 'jquery';
import KimaiReducedClickHandler from "./KimaiReducedClickHandler";

export default class KimaiAlternativeLinks extends KimaiReducedClickHandler {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        this._addClickHandler(this.selector, function(href) {
            window.location = href;
        });
    }

}
