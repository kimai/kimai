/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatatable: handles functionality for the datatable
 */

import jQuery from 'jquery';
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

        const attributes = dataTable.dataset;
        const events = attributes['reloadEvent'];

        if (events === undefined) {
            return;
        }

        const self = this;
        const handle = function() { self.reloadDatatable(); };

        for (let eventName of events.split(' ')) {
            document.addEventListener(eventName, handle);
        }

        if (this.getContainer().getConfiguration().get('autoReloadDatatable')) {
            document.addEventListener('toolbar-change', handle);
        } else {
            document.addEventListener('pagination-change', handle);
            document.addEventListener('filter-change', handle);
        }
    }

    reloadDatatable() {
        const contentArea = this.contentArea;
        const durations = this.getContainer().getPlugin('timesheet-duration');
        const toolbarSelector = this.getContainer().getPlugin('toolbar').getSelector();
        
        const form = jQuery(toolbarSelector);
        let loading = '<div class="overlay"><i class="fas fa-sync fa-spin"></i></div>';
        jQuery(contentArea).append(loading);

        // remove the empty fields to prevent errors
        let formData = jQuery(toolbarSelector + ' :input')
            .filter(function(index, element) {
                return jQuery(element).val() != '';
            })
            .serialize();

        jQuery.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            success: function(html) {
                jQuery(contentArea).replaceWith(
                    jQuery(html).find(contentArea)
                );
                durations.updateRecords();
            },
            error: function(xhr, err) {
                form.submit();
            }
        });

    }
}
