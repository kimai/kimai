/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (typeof jQuery === 'undefined') {
    throw new Error('Kimai requires jQuery');
}

/* kimai
 *
 * @type Object
 * @description $.kimai is the main object for the template's app.
 *              It's used for implementing functions and options related
 *              to the template. Keeping everything wrapped in an object
 *              prevents conflict with other plugins and is a better
 *              way to organize our code.
 */
$.kimai = {};

$(function() {
"use strict";

    $.kimai = {
        init: function(options) {
            if (typeof options !== 'undefined') {
                $.kimai.settings = $.extend({}, $.kimai.defaults, options);
            }

            // set the current locale for all javascript components
            moment.locale($.kimai.settings['locale']);

            // ask before a delete call is executed
            $('body').on('click', 'a.btn-trash', function (event) {
                return confirm($.kimai.settings['confirmDelete']);
            });

            // activate the dropdown functionality
            $('.dropdown-toggle').dropdown();

            // auto hide success message after x seconds, as they are just meant as quick feedback and
            // not as a permanent source of information
            if ($.kimai.settings['alertSuccessAutoHide'] > 0) {
                setTimeout(
                    function() {
                        $('div.alert-success').alert('close');
                    },
                    $.kimai.settings['alertSuccessAutoHide']
                );
            }

            // ==== compound field in toolbar ====
            $('input[data-daterangepickerenable="on"]').each(function(index) {
                var localeFormat = $(this).data('format');

                $(this).daterangepicker({
                    showDropdowns: true,
                    autoUpdateInput: false,
                    autoApply: false,
                    locale: {
                        format: localeFormat,
                        firstDay: 1
                    },
                    ranges: {
                        // TODO translate
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        //'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        //'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This week': [moment().startOf('week'), moment().endOf('week')],
                        'Last week': [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')],
                        'This month': [moment().startOf('month'), moment().endOf('month')],
                        'Last month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'This year': [moment().startOf('year'), moment().endOf('year')],
                        'Last year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
                    },
                    alwaysShowCalendars: true
                });

                $(this).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format(localeFormat) + ' - ' + picker.endDate.format(localeFormat));
                });
            });

            // ==== single select boxes in toolbars ====
            $('input[data-datepickerenable="on"]').each(function(index) {
                var localeFormat = $(this).data('format');
                $(this).daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    autoUpdateInput: false,
                    locale: {
                        format: localeFormat,
                        firstDay: 1
                    }
                });

                $(this).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format(localeFormat));
                    $(this).trigger("change");
                });
            });

            // ==== edit timesheet - date with time ====
            $('input[data-datetimepicker="on"]').each(function(index) {
                var localeFormat = $(this).data('format');
                $(this).daterangepicker({
                    singleDatePicker: true,
                    timePicker: true,
                    timePicker24Hour: true,
                    showDropdowns: true,
                    autoUpdateInput: false,
                    locale: {
                        // TODO translate
                        cancelLabel: 'Clear',
                        format: localeFormat,
                        firstDay: 1
                    }
                });

                $(this).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format(localeFormat));
                    $(this).trigger("change");
                });
            });

            $('select[data-related-select]').change(function() {
                var apiUrl = $(this).attr('data-api-url').replace('-s-', $(this).val());
                var targetSelect = $(this).attr('data-related-select');

                $.ajax({
                    url: apiUrl,
                    headers: {
                        'X-AUTH-SESSION': true,
                        'Content-Type':'application/json'
                    },
                    method: 'GET',
                    dataType: 'json',
                    success: function(data){
                        var selectName = '#' + targetSelect;
                        var $select = $(selectName);
                        var $emptyOption = $(selectName + ' option[value=""]');

                        $select.find('option').remove().end().find('optgroup').remove().end();

                        if ($emptyOption.length != 0) {
                            $select.append('<option value="">' + $emptyOption.text() + '</option>');
                        }

                        $.each(data, function(i, obj) {
                            $select.append('<option value="' + obj.id + '">' + obj.name + '</option>');
                        });

                        // if we don't trigger the change, the other selects won't be resetted
                        $select.trigger('change');

                        // if the beta test kimai.theme.select_type is active, this will tell the selects to refresh
                        $('.selectpicker').selectpicker('refresh');
                    }
                });
            });

            $('.markdown-body :header').prepend(function() {
                $(this).prepend('<a class="anchor" href="#'+$(this).attr('id')+'"><i class="fas fa-link"></i>');
            });

            $('.markdown-body :header').hover(function(){
                $(this).find('a.anchor').show();
            }, function(){
                $(this).find('a.anchor').hide();
            });
        },
        pauseRecord: function(selector) {
            $(selector + ' .pull-left i').hover(function () {
                var link = $(this).parents('a');
                link.attr('href', link.attr('href').replace('/stop', '/pause'));
                $(this).removeClass('fa-stop-circle').addClass('fa-pause-circle').addClass('text-orange');
            },function () {
                var link = $(this).parents('a');
                link.attr('href', link.attr('href').replace('/pause', '/stop'));
                $(this).removeClass('fa-pause-circle').removeClass('text-orange').addClass('fa-stop-circle');
            });
        }
    };

    // default values
    $.kimai.defaults = {
        locale: 'en',
        confirmDelete: 'Really delete?',
        alertSuccessAutoHide: 5000
    };

    // once initialized, here are all values
    $.kimai.settings = {};

});
