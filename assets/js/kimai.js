/*!
 * This file is part of the Kimai time-tracking app.
 *
 * Main JS application file for Kimai 2. This file should be included in all pages.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** global: jQuery */
/** global: moment */

if (typeof jQuery === 'undefined') {
    throw new Error('Kimai requires jQuery');
}

if (typeof moment === 'undefined') {
    throw new Error('Kimai requires moment.js');
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

            // activate the dropdown functionality
            $('.dropdown-toggle').dropdown();
            // activate the tooltip functionality
            $('[data-toggle="tooltip"]').tooltip();
            // auto hide success messages, as they are just meant as user feedback and not as a permanent information
            this.activateAutomaticAlertRemove('div.alert-success', 5000);
            // activate the (daterangepicker) compound field in toolbar
            this.activateDateRangePicker('.content-wrapper');
            // single select boxes in toolbars
            this.activateDatePicker('.content-wrapper');
            // edit timesheet - date with time
            this.activateDateTimePicker('.content-wrapper');
            // some actions can be performed in a modal for a better UX
            this.activateAjaxFormInModal('a.modal-ajax-form');
            // activate select boxes that load dynamic data via API
            this.activateApiSelects('select[data-related-select]');
        },
        reloadDatatableWithToolbarFilter: function() {
            // TODO check if toolbar form is present, if not, reload current URL
            var $form = $('.toolbar form');
            var loading = '<div class="overlay"><i class="fas fa-sync fa-spin"></i></div>';
            $('section.content').append(loading);
            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: $form.serialize(),
                success: function(html) {
                    $('section.content').replaceWith(
                        $(html).find('section.content')
                    );
                },
                error: function(xhr, err) {
                    $form.submit();
                }
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
        },
        activateAutomaticAlertRemove(selector, mseconds) {
            setTimeout(
                function() {
                    $(selector).alert('close');
                },
                mseconds
            );
        },
        activateApiSelects: function(selector) {
            $('body').on('change', selector, function(event) {
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

                        if ($emptyOption.length !== 0) {
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
        },
        activateDatePicker: function(selector) {
            $(selector + ' input[data-datepickerenable="on"]').each(function(index) {
                var localeFormat = $(this).data('format');
                $(this).daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    autoUpdateInput: false,
                    locale: {
                        format: localeFormat,
                        firstDay: 1,
                        applyLabel: $.kimai.settings['apply'],
                        cancelLabel: $.kimai.settings['cancel'],
                        customRangeLabel: $.kimai.settings['customRange']
                    }
                });

                $(this).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format(localeFormat));
                    $(this).trigger("change");
                });
            });
        },
        activateDateTimePicker: function(selector) {
            $(selector + ' input[data-datetimepicker="on"]').each(function(index) {
                var localeFormat = $(this).data('format');
                $(this).daterangepicker({
                    singleDatePicker: true,
                    timePicker: true,
                    timePicker24Hour: $.kimai.settings['twentyFourHours'],
                    showDropdowns: true,
                    autoUpdateInput: false,
                    locale: {
                        format: localeFormat,
                        firstDay: 1,
                        applyLabel: $.kimai.settings['apply'],
                        cancelLabel: $.kimai.settings['cancel'],
                        customRangeLabel: $.kimai.settings['customRange']
                    }
                });

                $(this).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format(localeFormat));
                    $(this).trigger("change");
                });
            });
        },
        activateDateRangePicker: function(selector) {
            $(selector + ' input[data-daterangepickerenable="on"]').each(function(index) {
                var localeFormat = $(this).data('format');
                var separator = $(this).data('separator');
                var rangesList = {};
                rangesList[$.kimai.settings['today']] = [moment(), moment()];
                rangesList[$.kimai.settings['yesterday']] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
                rangesList[$.kimai.settings['thisWeek']] = [moment().startOf('week'), moment().endOf('week')];
                rangesList[$.kimai.settings['lastWeek']] = [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')];
                rangesList[$.kimai.settings['thisMonth']] = [moment().startOf('month'), moment().endOf('month')];
                rangesList[$.kimai.settings['lastMonth']] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
                rangesList[$.kimai.settings['thisYear']] = [moment().startOf('year'), moment().endOf('year')];
                rangesList[$.kimai.settings['lastYear']] = [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')];

                $(this).daterangepicker({
                    showDropdowns: true,
                    autoUpdateInput: false,
                    autoApply: false,
                    linkedCalendars: false,
                    locale: {
                        separator: separator,
                        format: localeFormat,
                        firstDay: 1,
                        applyLabel: $.kimai.settings['apply'],
                        cancelLabel: $.kimai.settings['cancel'],
                        customRangeLabel: $.kimai.settings['customRange']
                    },
                    ranges: rangesList,
                    alwaysShowCalendars: true
                });

                $(this).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format(localeFormat) + ' - ' + picker.endDate.format(localeFormat));
                    $(this).trigger("change");
                });
            });
        },
        ajaxFormInModal: function(html) {
            // the modal that we use to render the form in
            var formIdentifier = '#remote_form_modal .modal-content form';
            var flashErrorIdentifier = 'div.alert-error';
            var $form = $(formIdentifier);
            var $modal = $('#remote_form_modal');

            // will be (re-)activated later
            $form.off('submit');

            // load new form from given content
            if ($(html).find('#form_modal .modal-content').length > 0 ) {
                // switch classes, in case the modal type changed
                $modal.on('hidden.bs.modal', function () {
                    if ($modal.hasClass('modal-danger')) {
                        $modal.removeClass('modal-danger');
                    }
                });

                if ($(html).find('#form_modal').hasClass('modal-danger')) {
                    $modal.addClass('modal-danger');
                }

                // TODO cleanup widgets before replacing the content?
                $('#remote_form_modal .modal-content').replaceWith(
                    $(html).find('#form_modal .modal-content')
                );
                // activate new loaded widgets
                $.kimai.activateDateTimePicker(formIdentifier);
            }

            // show error flash messages
            if ($(html).find(flashErrorIdentifier).length > 0) {
                $('#remote_form_modal .modal-body').prepend(
                    $(html).find(flashErrorIdentifier)
                );
            }

            // -----------------------------------------------------------------------
            // a fix for firefox focus problems with datepicker in modal
            // see https://github.com/kevinpapst/kimai2/issues/618
            var enforceModalFocusFn = $.fn.modal.Constructor.prototype.enforceFocus;
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $modal.on('hidden.bs.modal', function () {
                $.fn.modal.Constructor.prototype.enforceFocus = enforceModalFocusFn;
            });
            // -----------------------------------------------------------------------

            // workaround for autofocus attribute, as the modal "steals" it
            $modal.on('shown.bs.modal', function () {
                $(this).find('input[type=text],textarea,select').filter(':not("[data-datetimepicker=on]")').filter(':visible:first').focus().delay(1000).focus();
            });

            $modal.modal('show');

            // the new form that was loaded via ajax
            $form = $(formIdentifier);

            // click handler for modal save button, to send forms via ajax
            $form.on('submit', function(event){
                var btn = $(formIdentifier + ' button[type=submit]').button('loading');
                event.preventDefault();
                event.stopPropagation();
                $.ajax({
                    url: $form.attr('action'),
                    type: $form.attr('method'),
                    data: $form.serialize(),
                    success: function(html) {
                        btn.button('reset');
                        var hasFieldError = $(html).find('#form_modal .modal-content .has-error').length > 0;
                        var hasFormError = $(html).find('#form_modal .modal-content ul.list-unstyled li.text-danger').length > 0;
                        var hasFlashError = $(html).find(flashErrorIdentifier).length > 0;

                        if (hasFieldError || hasFormError || hasFlashError) {
                            $.kimai.ajaxFormInModal(html);
                        } else {
                            $.kimai.reloadDatatableWithToolbarFilter();
                            $modal.modal('hide');
                        }
                        return false;
                    },
                    error: function(xhr, err) {
                        // FIXME problem in google and 500 error, keeps on submitting...
                        // what else could we do? submitting again at least gives us the opportunity to see errors,
                        // which maybe would be hidden otherwise... this one is totally up for discussion!
                        $form.submit();
                    }
                });
            });
        },
        activateAjaxFormInModal: function(selector) {
            $('body').on('click', selector, function(event) {
                event.preventDefault();
                event.stopPropagation();
                $.ajax({
                    url: $(this).attr('href'),
                    success: function(html) {
                        $.kimai.ajaxFormInModal(html);
                    },
                    error: function(xhr, err) {
                        window.location = $(this).attr('href');
                    }
                });
            });
        }
    };

    // default values
    $.kimai.defaults = {
        locale: 'en',
        today: 'Today',
        yesterday: 'Yesterday',
        apply: 'Apply',
        cancel: 'Cancel',
        thisWeek: 'This week',
        lastWeek: 'Last week',
        thisMonth: 'This month',
        lastMonth: 'Last month',
        thisYear: 'This year',
        lastYear: 'Last year',
        customRange: 'Custom range',
        twentyFourHours: true
    };

    // once initialized, here are all values
    $.kimai.settings = {};

});
