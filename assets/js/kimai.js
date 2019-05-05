/*
 * This file is part of the Kimai time-tracking app.
 *
 * Main JS application file for Kimai 2. This file should be included in all pages.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] Main JS application file for Kimai 2
 */

import moment from 'moment';
import jQuery from 'jquery';
import KimaiTranslation from "./KimaiTranslation";
import KimaiConfiguration from "./KimaiConfiguration";
import KimaiCore from "./KimaiCore";
import KimaiActiveRecordsDuration from './plugins/KimaiActiveRecordsDuration.js';
import KimaiDatatableColumnView from './plugins/KimaiDatatableColumnView.js';
import KimaiThemeInitializer from "./plugins/KimaiThemeInitializer";
import KimaiJqueryPluginInitializer from "./plugins/KimaiJqueryPluginInitializer";
import KimaiDateRangePicker from "./plugins/KimaiDateRangePicker";

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

const KIMAI_EVENT_INITIALIZED = 'KimaiInitialized';
const KIMAI_EVENT_PLUGIN_REGISTER = 'KimaiPluginRegister';
const KIMAI_EVENT_PLUGIN_INITIALIZED = 'KimaiPluginInitialized';

jQuery(function() {
"use strict";

    $.kimai = {
        init: function(options) {
            // TODO split configuration and translation in two objects
            if (typeof options !== 'undefined') {
                $.kimai.settings = $.extend({}, $.kimai.defaults, options);
            }

            // set the current locale for all javascript components
            moment.locale($.kimai.settings['locale']);

            const kimai = new KimaiCore(
                new KimaiConfiguration($.kimai.settings),
                new KimaiTranslation($.kimai.settings)
            );

            kimai.registerPlugin(new KimaiActiveRecordsDuration('[data-since]'));
            kimai.registerPlugin(new KimaiDatatableColumnView());
            kimai.registerPlugin(new KimaiThemeInitializer());
            kimai.registerPlugin(new KimaiJqueryPluginInitializer());
            kimai.registerPlugin(new KimaiDateRangePicker('.content-wrapper'));

            // notify all listeners that Kimai plugins can now be registered
            this.sendEvent(KIMAI_EVENT_PLUGIN_REGISTER);

            // initialize all plugins
            kimai.getPlugins().map(plugin => { plugin.init(); });

            // notify all listeners that all plugins are now initialized
            this.sendEvent(KIMAI_EVENT_PLUGIN_INITIALIZED);

            // edit timesheet - date with time
            this.activateDateTimePicker('.content-wrapper');
            // some actions can be performed in a modal for a better UX
            this.activateAjaxFormInModal('.modal-ajax-form');
            // handle clicks on table rows
            this.activateAlternativeLinks('.alternative-link');
            // activate select boxes that load dynamic data via API
            this.activateApiSelects('select[data-related-select]');

            // notify all listeners that Kimai is now ready to be used
            this.sendEvent(KIMAI_EVENT_INITIALIZED);
        },
        sendEvent: function(name) {
            document.dispatchEvent(new Event(name));
        },
        reloadDatatableWithToolbarFilter: function() {
            // TODO check if toolbar form is present, if not, reload current URL
            let $form = jQuery('.toolbar form');
            let loading = '<div class="overlay"><i class="fas fa-sync fa-spin"></i></div>';
            jQuery('section.content').append(loading);

            // remove the empty fields to prevent errors
            let formData = jQuery('.toolbar form :input')
                .filter(function(index, element) {
                    return jQuery(element).val() != '';
                })
                .serialize();

            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: formData,
                success: function(html) {
                    jQuery('section.content').replaceWith(
                        jQuery(html).find('section.content')
                    );
                },
                error: function(xhr, err) {
                    $form.submit();
                }
            });
        },
        pauseRecord: function(selector) {
            jQuery(selector + ' .pull-left i').hover(function () {
                let link = jQuery(this).parents('a');
                link.attr('href', link.attr('href').replace('/stop', '/pause'));
                jQuery(this).removeClass('fa-stop-circle').addClass('fa-pause-circle').addClass('text-orange');
            },function () {
                let link = jQuery(this).parents('a');
                link.attr('href', link.attr('href').replace('/pause', '/stop'));
                jQuery(this).removeClass('fa-pause-circle').removeClass('text-orange').addClass('fa-stop-circle');
            });
        },
        activateApiSelects: function(selector) {
            const self = this;
            jQuery('body').on('change', selector, function(event) {
                let apiUrl = jQuery(this).attr('data-api-url').replace('-s-', jQuery(this).val());
                const targetSelect = '#' + jQuery(this).attr('data-related-select');

                // if the related target select does not exist, we do not need to load the related data
                if (jQuery(targetSelect).length === 0) {
                    return;
                }

                if (jQuery(this).val() === '') {
                    if (jQuery(this).attr('data-empty-url') === undefined) {
                        self.updateSelect(targetSelect, {});
                        jQuery(targetSelect).attr('disabled', 'disabled');
                        return;
                    }
                    apiUrl = jQuery(this).attr('data-empty-url').replace('-s-', jQuery(this).val());
                }

                jQuery(targetSelect).removeAttr('disabled');

                $.ajax({
                    url: apiUrl,
                    headers: {
                        'X-AUTH-SESSION': true,
                        'Content-Type':'application/json'
                    },
                    method: 'GET',
                    dataType: 'json',
                    success: function(data){
                        self.updateSelect(targetSelect, data);
                    }
                });
            });
        },
        updateSelect: function(selectName, data) {
            let $select = jQuery(selectName);
            let $emptyOption = jQuery(selectName + ' option[value=""]');

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
            jQuery('.selectpicker').selectpicker('refresh');
        },
        activateDateTimePicker: function(selector) {
            jQuery(selector + ' input[data-datetimepicker="on"]').each(function(index) {
                let localeFormat = jQuery(this).data('format');
                jQuery(this).daterangepicker({
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

                jQuery(this).on('apply.daterangepicker', function(ev, picker) {
                    jQuery(this).val(picker.startDate.format(localeFormat));
                    jQuery(this).trigger("change");
                });
            });
        },
        ajaxFormInModal: function(html) {
            // the modal that we use to render the form in
            var formIdentifier = '#remote_form_modal .modal-content form';
            var flashErrorIdentifier = 'div.alert-error';
            var $form = jQuery(formIdentifier);
            var $modal = jQuery('#remote_form_modal');

            // will be (re-)activated later
            $form.off('submit');

            // load new form from given content
            if (jQuery(html).find('#form_modal .modal-content').length > 0 ) {
                // switch classes, in case the modal type changed
                $modal.on('hidden.bs.modal', function () {
                    if ($modal.hasClass('modal-danger')) {
                        $modal.removeClass('modal-danger');
                    }
                });

                if (jQuery(html).find('#form_modal').hasClass('modal-danger')) {
                    $modal.addClass('modal-danger');
                }

                // TODO cleanup widgets before replacing the content?
                jQuery('#remote_form_modal .modal-content').replaceWith(
                    jQuery(html).find('#form_modal .modal-content')
                );
                // activate new loaded widgets
                $.kimai.activateDateTimePicker(formIdentifier);
            }

            // show error flash messages
            if (jQuery(html).find(flashErrorIdentifier).length > 0) {
                jQuery('#remote_form_modal .modal-body').prepend(
                    jQuery(html).find(flashErrorIdentifier)
                );
            }

            // -----------------------------------------------------------------------
            // a fix for firefox focus problems with datepicker in modal
            // see https://github.com/kevinpapst/kimai2/issues/618
            let enforceModalFocusFn = $.fn.modal.Constructor.prototype.enforceFocus;
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            $modal.on('hidden.bs.modal', function () {
                $.fn.modal.Constructor.prototype.enforceFocus = enforceModalFocusFn;
            });
            // -----------------------------------------------------------------------

            // workaround for autofocus attribute, as the modal "steals" it
            $modal.on('shown.bs.modal', function () {
                jQuery(this).find('input[type=text],textarea,select').filter(':not("[data-datetimepicker=on]")').filter(':visible:first').focus().delay(1000).focus();
            });

            $modal.modal('show');

            // the new form that was loaded via ajax
            $form = jQuery(formIdentifier);

            // click handler for modal save button, to send forms via ajax
            $form.on('submit', function(event){
                let btn = jQuery(formIdentifier + ' button[type=submit]').button('loading');
                event.preventDefault();
                event.stopPropagation();
                $.ajax({
                    url: $form.attr('action'),
                    type: $form.attr('method'),
                    data: $form.serialize(),
                    success: function(html) {
                        btn.button('reset');
                        let hasFieldError = jQuery(html).find('#form_modal .modal-content .has-error').length > 0;
                        let hasFormError = jQuery(html).find('#form_modal .modal-content ul.list-unstyled li.text-danger').length > 0;
                        let hasFlashError = jQuery(html).find(flashErrorIdentifier).length > 0;

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

        // allows to assign the given selector to any element, which then is used as click-handler:
        // opening a modal with the content from the URL given in the elements 'data-href' or 'href' attribute
        activateAjaxFormInModal: function(selector) {
            this._addClickHandlerReducedInTableRow(selector, function(href) {
                $.ajax({
                    url: href,
                    success: function(html) {
                        $.kimai.ajaxFormInModal(html);
                    },
                    error: function(xhr, err) {
                        window.location = href;
                    }
                });
            });
        },

        // allows to assign the given selector to any element, which then is used as click-handler:
        // redirecting to the URL given in the elements 'data-href' or 'href' attribute
        activateAlternativeLinks: function(selector) {
            this._addClickHandlerReducedInTableRow(selector, function(href) {
                window.location = href;
            });
        },

        _addClickHandlerReducedInTableRow: function(selector, callback)  {
            jQuery('body').on('click', selector, function(event) {
                // just in case an inner element is editable, than this should not be triggered
                if (event.target.parentNode.isContentEditable || event.target.isContentEditable) {
                    return;
                }

                // handles the "click" on table rows to open an entry for editing: when a button within a row is clicked,
                // we don't want the table row event to be processed - so we intercept it
                let target = event.target;
                if (event.currentTarget.matches('tr')) {
                    while (!target.matches('body')) {
                        if (target.matches('a') || target.matches ('button')) {
                            return;
                        }
                        target = target.parentNode;
                    }
                }

                event.preventDefault();
                event.stopPropagation();

                let href = jQuery(this).attr('data-href');
                if (!href) {
                    href = jQuery(this).attr('href');
                }

                callback(href);
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
