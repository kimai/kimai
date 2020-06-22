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

    constructor(formSelector, formSubmitActionClass) {
        super();
        this.formSelector = formSelector;
        this.actionClass = formSubmitActionClass;
    }

    getId() {
        return 'toolbar';
    }

    init() {
        const formSelector = this.getSelector();
        const self = this;
        const EVENT = self.getContainer().getPlugin('event');

        this._registerPagination(formSelector, EVENT);
        this._registerSortableTables(formSelector, EVENT);
        this._registerAlternativeSubmitActions(formSelector, this.actionClass);
        this._registerSearchButtons(formSelector);

        jQuery('body')
            // prevent that the dropdown closes, when a form input is changed - eg. a select option was clicked
            .on('click', formSelector + ' .dropdown-menu', function (event) {
                event.stopPropagation();
            })
            // prevent that a click into the search field will close the dropdown
            .on('click', '.select2-search__field', function (event) {
                event.stopPropagation();
            })
            // prevent that the dropdown closes when a optgroup header is clicked
            .on('click', '.select2-results__group', function (event) {
                event.stopPropagation();
            })
            // prevent that a click into a daterangepicker will close the dropdown
            .on('click', '.daterangepicker', function (event) {
                event.stopPropagation();
            })
            // prevent that clicks in the dropdown elements, but outside of elements will close the dropdown (eg border besides the search field)
            .on('click', '.select2-container', function (event) {
                event.stopPropagation();
            })
        ;

        // Reset the page if filter values are changed, otherwise we might end up with a limited set of data, 
        // which does not support the given page - and it would be just wrong to stay in the same page
        jQuery(formSelector +' input').change(function (event) {
            switch (event.target.id) {
                case 'order':
                case 'orderBy':
                case 'page':
                    break;
                default:
                    jQuery(formSelector + ' input#page').val(1);
                    break;
            }
            self.triggerChange();
        });
        
        // when user selected a new customer or project, reset the pagination back to 1
        // and then find out if the results should be reloaded
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

        // close all open selectpicker upon choosing any dropdown option
        jQuery(formSelector + ' select.selectpicker').on('change', function(event) {
            jQuery('.bootstrap-select.open').removeClass('open');
        });
        
    }

    /**
     * The search toggle button is not part of this component, but it is directly connected to it.
     * @private
     */
    _registerSearchButtons(formSelector) {
        jQuery('body')
            // only for mobile experience currently: show the search form field
            .on('click', '.btn-search.search-toggle', function (event) {
                event.preventDefault();
                event.stopPropagation();
                jQuery(formSelector).parent('section').toggleClass('search-open');
                jQuery(formSelector).toggleClass('hidden-xs');
                jQuery(formSelector + ' input#searchTerm').dropdown('toggle');
                jQuery(formSelector + ' input#searchTerm').focus();
            })
            // hide the search form field
            .on('click', formSelector + ' a.search-cancel', function (event) {
                event.preventDefault();
                event.stopPropagation();
                jQuery(formSelector).parent('section').toggleClass('search-open');
                jQuery(formSelector + ' input#searchTerm').dropdown('toggle');
                jQuery(formSelector).toggleClass('hidden-xs');
            })
        ;
    }

    /**
     * Some actions utilize the filter from the search form and submit it to another URL.
     * @private
     */
    _registerAlternativeSubmitActions(toolbarSelector, actionBtnClass) {
        document.addEventListener('click', function(event) {
            let target = event.target;
            while (target !== null && !target.matches('body')) {
                if (target.classList.contains(actionBtnClass)) {
                    const form = document.querySelector(toolbarSelector);
                    if (form === null) {
                        return;
                    }
                    const prevAction = form.action;
                    const prevMethod = form.method;
                    form.target = '_blank';
                    form.action = target.href;
                    if (target.dataset.method !== undefined) {
                        form.method = target.dataset.method;
                    }
                    form.submit();
                    form.target = '';
                    form.action = prevAction;
                    form.method = prevMethod;

                    event.preventDefault();
                    event.stopPropagation();
                }

                target = target.parentNode;
            }
        });        
    }

    /**
     * Sortable datatables use hidden fields in the toolbar filter/search form
     * @private
     */
    _registerSortableTables(formSelector, EVENT) {
        jQuery('body').on('click', 'th.sortable', function(event){
            var $header = jQuery(event.target);
            var order = 'DESC';
            var orderBy = $header.data('order');
            if ($header.hasClass('sorting_desc')) {
                order = 'ASC';
            }
            jQuery(formSelector + ' input#orderBy').val(orderBy);
            jQuery(formSelector + ' input#order').val(order);
            // triggers the page reset - see below
            jQuery(formSelector + ' input#order').trigger('change');
            // triggers the datatable reload - search for the event name
            EVENT.trigger('filter-change');
        });
    }
    
    /**
     * This catches all clicks on the pagination and prevents the default action, as we want to reload the page via JS
     * @private
     */
    _registerPagination(formSelector, EVENT) {
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
            EVENT.trigger('pagination-change');
            return false;
        });

    }
    
    hide() {
        const toggle = jQuery(this.getSelector() + ' #searchTerm.dropdown-toggle');
        if (toggle.parent().hasClass('open')) {
            toggle.dropdown('toggle');
        }
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
        return this.formSelector;
    }

}
