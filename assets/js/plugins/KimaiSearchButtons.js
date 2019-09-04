/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiSearchButtons: handles events of search buttons and the filter dropdown
 */

import jQuery from 'jquery';
import KimaiPlugin from "../KimaiPlugin";

/**
 * FIXME refactor me and merge with KimaiToolbar
 */
export default class KimaiSearchButtons extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        const self = this;

        $(document).on('click', this.selector + ' .search-toggle', function (e) {
            e.stopPropagation();
            jQuery(self.selector).toggleClass('search-open');
            jQuery(self.selector + ' form.header-search').toggleClass('hidden-xs');
            jQuery(self.selector + ' form.header-search .dropdown-toggle').dropdown('toggle');
            jQuery(self.selector + ' form.header-search input#searchTerm').focus();
        });

        $(document).on('click', this.selector + ' .search-cancel', function (e) {
            e.preventDefault();
            jQuery(self.selector).toggleClass('search-open');
            jQuery(self.selector + ' form.header-search .dropdown-toggle').dropdown('toggle');
            jQuery(self.selector + ' form.header-search').toggleClass('hidden-xs');
        });

        // prevent that the dropdown closes, when a form input is changed - eg. a select option was clicked
        $(document).on('click', this.selector + ' .dropdown-menu', function (e) {
            e.stopPropagation();
        });
    }

}
