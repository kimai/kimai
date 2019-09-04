/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiToolbar: some event listener to handle the toolbar/data-table filter, toolbar and navigation
 */

import jQuery from 'jquery';
import KimaiPlugin from "../KimaiPlugin";

export default class KimaiToolbar extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'toolbar';
    }

    init() {
        const formSelector = this.getSelector();
        const self = this;

        // This catches all clicks on the pagination and prevents the default action, as we want to reload the page via JS
        jQuery('body').on('click', 'div.navigation ul.pagination li a', function(event) {
            let pager = jQuery(formSelector + " input#page");
            if (pager.length === 0) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            let urlParts = jQuery(this).attr('href').split('/');
            let page = urlParts[urlParts.length-1];
            pager.val(page);
            pager.trigger('change');
            self.getContainer().getPlugin('event').trigger('pagination-change');
            return false;
        });

        // Reset the page if any other value is changed, otherwise we might end up with a limited set
        // of data which does not support the given page - and it would be just wrong to stay in the same page
        jQuery(this.selector +' input').change(function (event) {
            switch (event.target.id) {
                case 'page':
                    break;
                default:
                    jQuery(formSelector + ' input#page').val(1);
            }
            self.triggerChange();
        });

        jQuery(formSelector + ' select').change(function (event) {
            let reload = true;
            switch (event.target.id) {
                case 'customer':
                    if (jQuery(formSelector + ' select#project').length > 0) {
                        reload = false;
                    }
                    break;

                case 'project':
                    if (jQuery(formSelector + ' select#activity').length > 0) {
                        reload = false;
                    }
                    break;
            }
            jQuery(formSelector + ' input#page').val(1);

            if (reload) {
                self.triggerChange();
            }
        });
    }

    /**
     * Triggers an event, that everyone can listen for.
     */
    triggerChange() {
        this.getContainer().getPlugin('event').trigger('toolbar-change');
    }

    /**
     * Returns the CSS selector to target the toolbar form.
     * 
     * @returns {string}
     */
    getSelector() {
        return this.selector;
    }

}
