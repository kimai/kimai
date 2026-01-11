/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/*!
 * [KIMAI] KimaiSortable: allow sorting of HTML elements
 */

import KimaiPlugin from "../KimaiPlugin";
import Sortable from 'sortablejs';

export default class KimaiSortable extends KimaiPlugin {

    getId() {
        return 'sortable';
    }

    /**
     * Enable sorting on the given selector
     *
     * @param {HTMLElement} element
     * @returns {true}
     */
    toggle(element) {
        if (!element instanceof HTMLElement) {
            console.error('Given element is invalid');
            return false;
        }

        let sortable = Sortable.get(element);
        if (sortable === undefined || sortable === null) {
            Sortable.create(element);
            return true;
        }

        sortable.destroy();
        return false;
    }

    toArray(element) {
        let sortable = Sortable.get(element);
        if (sortable === undefined || sortable === null) {
            return null;
        }
        return sortable.toArray();
    }
}
