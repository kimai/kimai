/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDateRangePicker: activate the (daterange picker) compound field in toolbar
 */

import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';
import moment from 'moment';

export default class KimaiDateRangePicker extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'date-range-picker';
    }

    init() {
        this.activateDateRangePicker(this.selector);
    }

    activateDateRangePicker(selector) {
        let translator = this.getContainer().getTranslation();
        jQuery(selector + ' input[data-daterangepickerenable="on"]').each(function(index) {
            let localeFormat = jQuery(this).data('format');
            let separator = jQuery(this).data('separator');
            let rangesList = {};

            rangesList[translator.get('today')] = [moment(), moment()];
            rangesList[translator.get('yesterday')] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
            rangesList[translator.get('thisWeek')] = [moment().startOf('week'), moment().endOf('week')];
            rangesList[translator.get('lastWeek')] = [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')];
            rangesList[translator.get('thisMonth')] = [moment().startOf('month'), moment().endOf('month')];
            rangesList[translator.get('lastMonth')] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
            rangesList[translator.get('thisYear')] = [moment().startOf('year'), moment().endOf('year')];
            rangesList[translator.get('lastYear')] = [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')];

            jQuery(this).daterangepicker({
                showDropdowns: true,
                autoUpdateInput: false,
                autoApply: false,
                linkedCalendars: false,
                locale: {
                    separator: separator,
                    format: localeFormat,
                    firstDay: 1,
                    applyLabel: translator.get('apply'),
                    cancelLabel: translator.get('cancel'),
                    customRangeLabel: translator.get('customRange'),
                    daysOfWeek: moment.weekdaysShort(),
                    monthNames: moment.months(),
                },
                ranges: rangesList,
                alwaysShowCalendars: true
            });

            jQuery(this).on('apply.daterangepicker', function(ev, picker) {
                jQuery(this).val(picker.startDate.format(localeFormat) + ' - ' + picker.endDate.format(localeFormat));
                jQuery(this).trigger("change");
            });
        });
    }

}
