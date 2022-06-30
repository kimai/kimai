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
        this.contentArea = contentAreaSelector;
        this.selector = tableSelector;
    }

    getId() {
        return 'datatable';
    }

    init() {
        const dataTable = document.querySelector(this.selector);

        // not every page contains a dataTable
        if (dataTable === null) {
            return;
        }

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

    reloadDatatable() {
        /** @type {KimaiActiveRecordsDuration} durations */
        const durations = this.getContainer().getPlugin('timesheet-duration');
        const toolbarSelector = this.getContainer().getPlugin('toolbar').getSelector();

        /** @type {HTMLFormElement} form */
        const form = document.querySelector(toolbarSelector);
        document.dispatchEvent(new CustomEvent('kimai.reloadContent', {detail: this.contentArea}));

        this.fetchForm(form)
        .then(response => {
            const temp = document.createElement('div');
            response.text().then((text) => {
                temp.innerHTML = text;
                const newContent = temp.querySelector(this.contentArea);
                document.querySelector(this.contentArea).replaceWith(newContent);
                durations.updateRecords();
                document.dispatchEvent(new Event('kimai.reloadedContent'));
            });
        })
        .catch(error => {
            form.submit();
        });
    }
}
