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

    activateDateRangePicker(selector) {
        const TRANSLATE = this.getContainer().getTranslation();
        const DATE_UTILS = this.getContainer().getPlugin('date');

        jQuery(selector + ' ' + this.selector).each(function(index) {
            let localeFormat = jQuery(this).data('format');
            let separator = jQuery(this).data('separator');
            let rangesList = {};

            rangesList[TRANSLATE.get('today')] = [moment(), moment()];
            rangesList[TRANSLATE.get('yesterday')] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
            rangesList[TRANSLATE.get('thisWeek')] = [moment().startOf('week'), moment().endOf('week')];
            rangesList[TRANSLATE.get('lastWeek')] = [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')];
            rangesList[TRANSLATE.get('thisMonth')] = [moment().startOf('month'), moment().endOf('month')];
            rangesList[TRANSLATE.get('lastMonth')] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
            rangesList[TRANSLATE.get('thisYear')] = [moment().startOf('year'), moment().endOf('year')];
            rangesList[TRANSLATE.get('lastYear')] = [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')];

            jQuery(this).daterangepicker({
                showDropdowns: true,
                autoUpdateInput: false,
                autoApply: false,
                linkedCalendars: false,
                locale: {
                    separator: separator,
                    format: localeFormat,
                    firstDay: 1,
                    applyLabel: TRANSLATE.get('confirm'),
                    cancelLabel: TRANSLATE.get('cancel'),
                    customRangeLabel: TRANSLATE.get('customRange'),
                    daysOfWeek: DATE_UTILS.getWeekDaysShort(),
                    monthNames: DATE_UTILS.getMonthNames(),
                },
                ranges: rangesList,
                alwaysShowCalendars: true
            });

            jQuery(this).on('apply.daterangepicker', function(ev, picker) {
                jQuery(this).val(picker.startDate.format(localeFormat) + ' - ' + picker.endDate.format(localeFormat));
                jQuery(this).data('begin', picker.startDate.format(localeFormat));
                jQuery(this).data('end', picker.endDate.format(localeFormat));
                jQuery(this).trigger("change");
            });
        });
    }

    destroyDateRangePicker(selector) {
        jQuery(selector + ' ' + this.selector).each(function(index) {
            if (jQuery(this).data('daterangepicker') !== undefined) {
                jQuery(this).daterangepicker('destroy');
                jQuery(this).data('daterangepicker').remove();
            }
        });
    }

}
