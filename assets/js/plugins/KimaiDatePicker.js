/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatePicker: single date selects (currently unused)
 */

import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';
import moment from 'moment';

export default class KimaiDatePicker extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'date-picker';
    }

    init() {
        this.activateDatePicker(this.selector);
    }

    activateDatePicker(selector) {
        let translator = this.getContainer().getTranslation();
        jQuery(selector + ' input[data-datepickerenable="on"]').each(function(index) {
            let localeFormat = jQuery(this).data('format');
            jQuery(this).daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: {
                    format: localeFormat,
                    firstDay: 1,
                    applyLabel: translator.get('confirm'),
                    cancelLabel: translator.get('cancel'),
                    customRangeLabel: translator.get('customRange'),
                    daysOfWeek: moment.weekdaysShort(),
                    monthNames: moment.months(),
                }
            });

            jQuery(this).on('apply.daterangepicker', function(ev, picker) {
                jQuery(this).val(picker.startDate.format(localeFormat));
                jQuery(this).trigger("change");
            });
        });
    }

}
