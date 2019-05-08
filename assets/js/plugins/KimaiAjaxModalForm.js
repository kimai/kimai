/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAjaxModalForm
 *
 * allows to assign the given selector to any element, which then is used as click-handler:
 * opening a modal with the content from the URL given in the elements 'data-href' or 'href' attribute
 */

import jQuery from 'jquery';
import KimaiClickHandlerReducedInTableRow from "./KimaiClickHandlerReducedInTableRow";

export default class KimaiAjaxModalForm extends KimaiClickHandlerReducedInTableRow {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        const self = this;

        this._addClickHandlerReducedInTableRow(this.selector, function(href) {
            jQuery.ajax({
                url: href,
                success: function(html) {
                    self._openFormInModal(html);
                },
                error: function(xhr, err) {
                    window.location = href;
                }
            });
        });
    }

    _openFormInModal(html) {
        const self = this;

        // the modal that we use to render the form in
        let formIdentifier = '#remote_form_modal .modal-content form';
        let flashErrorIdentifier = 'div.alert-error';
        let form = jQuery(formIdentifier);
        let remoteModal = jQuery('#remote_form_modal');

        // will be (re-)activated later
        form.off('submit');

        // load new form from given content
        if (jQuery(html).find('#form_modal .modal-content').length > 0 ) {
            // switch classes, in case the modal type changed
            remoteModal.on('hidden.bs.modal', function () {
                if (remoteModal.hasClass('modal-danger')) {
                    remoteModal.removeClass('modal-danger');
                }
            });

            if (jQuery(html).find('#form_modal').hasClass('modal-danger')) {
                remoteModal.addClass('modal-danger');
            }

            jQuery('#remote_form_modal .modal-content').replaceWith(
                jQuery(html).find('#form_modal .modal-content')
            );

            // activate new loaded widgets
            self.getContainer().getPlugin('date-time-picker').activateDateTimePicker(formIdentifier);
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
        let enforceModalFocusFn = jQuery.fn.modal.Constructor.prototype.enforceFocus;
        jQuery.fn.modal.Constructor.prototype.enforceFocus = function() {};
        remoteModal.on('hidden.bs.modal', function () {
            jQuery.fn.modal.Constructor.prototype.enforceFocus = enforceModalFocusFn;
        });
        // -----------------------------------------------------------------------

        // workaround for autofocus attribute, as the modal "steals" it
        remoteModal.on('shown.bs.modal', function () {
            jQuery(this).find('input[type=text],textarea,select').filter(':not("[data-datetimepicker=on]")').filter(':visible:first').focus().delay(1000).focus();
        });

        remoteModal.modal('show');

        // the new form that was loaded via ajax
        form = jQuery(formIdentifier);

        // click handler for modal save button, to send forms via ajax
        form.on('submit', function(event){
            let btn = jQuery(formIdentifier + ' button[type=submit]').button('loading');
            let eventName = form.attr('data-form-event');
            let events = self.getContainer().getPlugin('event');

            event.preventDefault();
            event.stopPropagation();

            jQuery.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                success: function(html) {
                    btn.button('reset');
                    let hasFieldError = jQuery(html).find('#form_modal .modal-content .has-error').length > 0;
                    let hasFormError = jQuery(html).find('#form_modal .modal-content ul.list-unstyled li.text-danger').length > 0;
                    let hasFlashError = jQuery(html).find(flashErrorIdentifier).length > 0;

                    if (hasFieldError || hasFormError || hasFlashError) {
                        self._openFormInModal(html);
                    } else {
                        events.trigger(eventName);
                        remoteModal.modal('hide');
                    }
                    return false;
                },
                error: function(xhr, err) {
                    // FIXME problem in google and 500 error, keeps on submitting...
                    // what else could we do? submitting again at least gives us the opportunity to see errors,
                    // which maybe would be hidden otherwise... this one is totally up for discussion!
                    form.submit();
                }
            });
        });
    }

}
