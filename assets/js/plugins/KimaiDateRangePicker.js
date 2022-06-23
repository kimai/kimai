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

export default class KimaiDateRangePicker extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'date-range-picker';
    }

    activateDateRangePicker(selector) {
        const TRANSLATE = this.getTranslation();
        const DATE_UTILS = this.getDateUtils();
        const firstDow = this.getConfigurations().getFirstDayOfWeek(false);

        jQuery(selector + ' ' + this.selector).each(function(index) {
            const localeFormat = jQuery(this).data('format');

            jQuery(this).daterangepicker({
                showDropdowns: true,
                autoUpdateInput: false,
                autoApply: false,
                linkedCalendars: true,
                drops: 'down',
                locale: {
                    separator: jQuery(this).data('separator'),
                    format: localeFormat,
                    firstDay: firstDow,
                    applyLabel: TRANSLATE.get('confirm'),
                    cancelLabel: TRANSLATE.get('cancel'),
                    customRangeLabel: TRANSLATE.get('customRange'),
                    daysOfWeek: DATE_UTILS.getWeekDaysShort(),
                    monthNames: DATE_UTILS.getMonthNames(),
                },
                ranges: DATE_UTILS.getFormDateRangeList(),
                alwaysShowCalendars: true
            });

            jQuery(this).on('show.daterangepicker', function (ev, picker) {
                if (picker.element.offset().top - jQuery(window).scrollTop() + picker.container.outerHeight() + 30 > jQuery(window).height()) {
                    // "up" is not possible here, because the code is triggered on many mobile phones and the picker then appears out of window
                    picker.drops = 'auto';
                    picker.move();
                }
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
                jQuery(this).data('daterangepicker').remove();
            }
        });
    }

}
