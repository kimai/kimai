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

        this.fixDropdowns();

        if (events === undefined) {
            return;
        }

        const self = this;
        const handle = function() { self.reloadDatatable(); };

        for (let eventName of events.split(' ')) {
            document.addEventListener(eventName, handle);
        }

        if (this.getConfiguration('autoReloadDatatable')) {
            document.addEventListener('toolbar-change', handle);
        } else {
            document.addEventListener('pagination-change', handle);
            document.addEventListener('filter-change', handle);
        }
    }

    reloadDatatable() {
        const self = this;
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
                self.fixDropdowns();
            },
            error: function(xhr, err) {
                form.submit();
            }
        });

    }

    /**
     * show dropdown menu upwards, if it is outside the visible viewport
     */
    fixDropdowns() {
        const docHeight = jQuery(document).height();
        jQuery(this.selector + ' [data-toggle=dropdown]').each(function() {
            const parent = jQuery(this).parent();
            const menu = parent.find('.dropdown-menu');

            if (parent && menu) {
                if ((parent.offset().top + parent.outerHeight() + menu.outerHeight()) > docHeight) {
                    parent.addClass('dropup').removeClass('dropdown');
                }
            }
        });
    }
}
