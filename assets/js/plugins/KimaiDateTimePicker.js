/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDateTimePicker: activate the (datetime picker) field in timesheet edit dialog
 */

import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';
import moment from 'moment';

export default class KimaiDateTimePicker extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'date-time-picker';
    }

    init() {
        this.activateDateTimePicker(this.selector);
    }

    activateDateTimePicker(selector) {
        let translator = this.getContainer().getTranslation();
        let configuration = this.getContainer().getConfiguration();

        jQuery(selector + ' input[data-datetimepicker="on"]').each(function(index) {
            let localeFormat = jQuery(this).data('format');
            jQuery(this).daterangepicker({
                singleDatePicker: true,
                timePicker: true,
                timePicker24Hour: configuration.get('twentyFourHours'),
                showDropdowns: true,
                autoUpdateInput: false,
                locale: {
                    format: localeFormat,
                    firstDay: 1,
                    applyLabel: translator.get('apply'),
                    cancelLabel: translator.get('cancel'),
                    customRangeLabel: translator.get('customRange'),
                    daysOfWeek: [
                        moment.weekdaysShort(0),
                        moment.weekdaysShort(1),
                        moment.weekdaysShort(2),
                        moment.weekdaysShort(3),
                        moment.weekdaysShort(4),
                        moment.weekdaysShort(5),
                        moment.weekdaysShort(6),
                    ],
                    monthNames: [
                        moment.months(1),
                        moment.months(2),
                        moment.months(3),
                        moment.months(4),
                        moment.months(5),
                        moment.months(6),
                        moment.months(7),
                        moment.months(8),
                        moment.months(9),
                        moment.months(10),
                        moment.months(11),
                        moment.months(12),
                    ],
                }
            });

            jQuery(this).on('apply.daterangepicker', function(ev, picker) {
                jQuery(this).val(picker.startDate.format(localeFormat));
                jQuery(this).trigger("change");
            });
        });
    }

}
