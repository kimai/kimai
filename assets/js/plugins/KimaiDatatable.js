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
import KimaiContextMenu from "../widgets/KimaiContextMenu";

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

        this.registerContextMenu(this._selector);

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

    /**
     * @param {string} selector
     * @private
     */
    registerContextMenu(selector)
    {
        KimaiContextMenu.createForDataTable(selector);
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
            this.registerContextMenu(this._selector);
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
