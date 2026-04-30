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
import DOMPurify from "dompurify";

export default class KimaiEscape extends KimaiPlugin {

    getId() {
        return 'escape';
    }

    /**
     * @param {string} title
     * @returns {string}
     */
    escapeForHtml(title) {
        if (title === undefined || title === null) {
            return '';
        }

        const charToReplace = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
        };

        return title.replace(/[&<>"]/g, function(tag) {
            return charToReplace[tag] || tag;
        });
    }

    /**
     * @param {string} html
     * @returns {string}
     */
    sanitize(html) {
        return DOMPurify.sanitize(html);
    }
}
