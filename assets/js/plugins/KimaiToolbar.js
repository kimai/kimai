/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiToolbar: some event listener to handle the toolbar/data-table filter, toolbar and navigation
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiToolbar extends KimaiPlugin {

    constructor(formSelector, formSubmitActionClass) {
        super();
        this._formSelector = formSelector;
        this._actionClass = formSubmitActionClass;
    }

    getId() {
        return 'toolbar';
    }

    init() {
        const formSelector = this.getSelector();

        this._registerPagination(formSelector);
        this._registerSortableTables(formSelector);
        this._registerAlternativeSubmitActions(formSelector, this._actionClass);

        // Reset the page if filter values are changed, otherwise we might end up with a limited set of data,
        // which does not support the given page - and it would be just wrong to stay in the same page
        [].slice.call(document.querySelectorAll(formSelector + ' input')).map((element) => {
            element.addEventListener('change', (event) => {
                switch (event.target.id) {
                    case 'order':
                    case 'orderBy':
                    case 'page':
                        break;
                    default:
                        document.querySelector(formSelector + ' input#page').value = 1;
                        break;
                }
            });
            this.triggerChange();
        });

        // when user selected a new customer or project, reset the pagination back to 1
        // and then find out if the results should be reloaded
        [].slice.call(document.querySelectorAll(formSelector + ' select')).map((element) => {
            element.addEventListener('change', (event) => {
                let reload = true;
                switch (event.target.id) {
                    case 'customer':
                        if (document.querySelector(formSelector + ' select#project') !== null) {
                            reload = false;
                        }
                        break;

                    case 'project':
                        if (document.querySelector(formSelector + ' select#activity') !== null) {
                            reload = false;
                        }
                        break;
                }
                document.querySelector(formSelector + ' input#page').value = 1;

                if (reload) {
                    this.triggerChange();
                }
            });
        });
    }

    /**
     * Some actions utilize the filter from the search form and submit it to another URL.
     * @private
     */
    _registerAlternativeSubmitActions(toolbarSelector, actionBtnClass) {
        document.addEventListener('click', function(event) {
            let target = event.target;
            while (target !== null && typeof target.matches === "function" && !target.matches('body')) {
                if (target.classList.contains(actionBtnClass)) {
                    const form = document.querySelector(toolbarSelector);
                    if (form === null) {
                        return;
                    }
                    const prevAction = form.getAttribute('action');
                    const prevMethod = form.getAttribute('method');
                    if (target.dataset.target !== undefined) {
                        form.target = target.dataset.target;
                    }
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
    _registerSortableTables(formSelector) {
        document.body.addEventListener('click', (event) => {
            if (!event.target.matches('th.sortable')) {
                return;
            }
            let order = 'DESC';
            let orderBy = event.target.dataset['order'];
            if (event.target.classList.contains('sorting_desc')) {
                order = 'ASC';
            }

            document.querySelector(formSelector + ' #orderBy').value = orderBy;
            document.querySelector(formSelector + ' #order').value = order;

            // re-render the selectbox
            document.querySelector(formSelector + ' #orderBy').dispatchEvent(new Event('change'));
            document.querySelector(formSelector + ' #order').dispatchEvent(new Event('change'));

            // triggers the datatable reload - search for the event name
            document.dispatchEvent(new Event('filter-change'));
        });
    }
    
    /**
     * This catches all clicks on the pagination and prevents the default action,
     * as we want to reload the page via JS.
     *
     * @private
     */
    _registerPagination(formSelector) {
        document.body.addEventListener('click', (event) => {
            if (!event.target.matches('ul.pagination li a') && (event.target.parentNode === null || !event.target.parentNode.matches('ul.pagination li a'))) {
                return;
            }

            let pager = document.querySelector(formSelector + " input#page");
            if (pager === null) {
                return;
            }
            let target = event.target;

            // this happens for the arrows, which can be an icon <i> element
            if (!target.matches('a')) {
                target = target.parentNode;
            }

            event.preventDefault();
            event.stopPropagation();
            let urlParts = target.href.split('/');
            let pageNumber = urlParts[urlParts.length - 1];
            // page number usually is the default value and is therefor missing from the URL
            if (!/\d/.test(pageNumber)) {
                pageNumber = 1;
            }
            pager.value = pageNumber;
            pager.dispatchEvent(new Event('change'));
            document.dispatchEvent(new Event('pagination-change'));
            return false;
        });

    }
    
    /**
     * Triggers an event, that everyone can listen for.
     */
    triggerChange() {
        document.dispatchEvent(new Event('toolbar-change'));
    }

    /**
     * Returns the CSS selector to target the toolbar form.
     * 
     * @returns {string}
     */
    getSelector() {
        return this._formSelector;
    }

}
