/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatatable: handles functionality for the datatable
 */

import KimaiPlugin from "../KimaiPlugin";

export default class KimaiDatatable extends KimaiPlugin {

    constructor(contentAreaSelector, tableSelector) {
        super();
        this._contentArea = contentAreaSelector;
        this._selector = tableSelector;
    }

    getId() {
        return 'datatable';
    }

    init() {
        const dataTable = document.querySelector(this._selector);

        // not every page contains a dataTable
        if (dataTable === null) {
            return;
        }

        this.registerContextMenu();

        const events = dataTable.dataset['reloadEvent'];
        if (events === undefined) {
            return;
        }

        const handle = () => { this.reloadDatatable(); };

        for (let eventName of events.split(' ')) {
            document.addEventListener(eventName, handle);
        }

        document.addEventListener('pagination-change', handle);
        document.addEventListener('filter-change', handle);
    }

    registerContextMenu()
    {
        const dataTable = document.querySelector(this._selector);
        const contextMenuId = dataTable.dataset['contextMenu'];
        if (contextMenuId !== undefined) {
            dataTable.addEventListener('contextmenu', (jsEvent) => {
                const dropdownElement = document.getElementById(contextMenuId);
                if (dropdownElement === null) {
                    return;
                }

                let target = jsEvent.target;
                while (target !== null) {
                    const tagName = target.tagName.toUpperCase();
                    if (tagName === 'TH' || tagName === 'TABLE' || tagName === 'BODY') {
                        return;
                    }

                    if (tagName === 'TR') {
                        break;
                    }

                    target = target.parentNode;
                }

                if (target === null || !target.matches('table.dataTable tbody tr')) {
                    return;
                }

                const actions = target.querySelector('td.actions div.dropdown-menu');
                if (actions === null) {
                    return;
                }

                jsEvent.preventDefault();

                if (dropdownElement.dataset['_listener'] === undefined) {
                    const dropdownListener = function() {
                        dropdownElement.classList.remove('d-block');
                        if (!dropdownElement.classList.contains('d-none')) {
                            dropdownElement.classList.add('d-none');
                        }
                    }
                    dropdownElement.addEventListener('click', dropdownListener);
                    document.addEventListener('click', dropdownListener);
                    dropdownElement.dataset['_listener'] = 'true';
                }

                dropdownElement.style.zIndex = '1021';
                dropdownElement.innerHTML = actions.innerHTML;
                dropdownElement.style.position = 'fixed';
                dropdownElement.style.top = (jsEvent.clientY) + 'px';
                dropdownElement.style.left = (jsEvent.clientX) + 'px';
                dropdownElement.classList.remove('d-none');
                if (!dropdownElement.classList.contains('d-block')) {
                    dropdownElement.classList.add('d-block');
                }
            });
        }
    }

    reloadDatatable()
    {
        const toolbarSelector = this.getContainer().getPlugin('toolbar').getSelector();

        /** @type {HTMLFormElement} form */
        const form = document.querySelector(toolbarSelector);
        const callback = (text) => {
            const temp = document.createElement('div');
            temp.innerHTML = text;
            const newContent = temp.querySelector(this._contentArea);
            document.querySelector(this._contentArea).replaceWith(newContent);
            this.registerContextMenu();
            document.dispatchEvent(new Event('kimai.reloadedContent'));
        };

        document.dispatchEvent(new CustomEvent('kimai.reloadContent', {detail: this._contentArea}));

        if (form === null) {
            this.fetch(document.location)
                .then(response => {
                    response.text().then(callback);
                })
                .catch(() => {
                    document.location.reload();
                });
            return;
        }

        this.fetchForm(form)
        .then(response => {
            response.text().then(callback);
        })
        .catch(() => {
            form.submit();
        });
    }
}
