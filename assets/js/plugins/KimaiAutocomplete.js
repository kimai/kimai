/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import jQuery from 'jquery';
import KimaiPlugin from "../KimaiPlugin";

/**
 * Supporting auto-complete fields via API.
 * Currently used for timesheet tagging in toolbar and edit dialogs.
 */
export default class KimaiAutocomplete extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    init() {
        this.minChars = this.getContainer().getConfiguration().get('autoComplete');
    }

    getId() {
        return 'autocomplete';
    }

    splitTagList(val) {
        return val.split(/,\s*/);
    }

    extractLastTag(term) {
        return this.splitTagList(term).pop();
    }

    activateAutocomplete(selector) {
        const self = this;
        
        jQuery(selector + ' ' + this.selector).each(function(index) {
            const currentField = jQuery(this);
            const apiUrl = currentField.attr('data-autocomplete-url');
            const API = self.getContainer().getPlugin('api');

            currentField
                // don't navigate away from the field on tab when selecting an item
                .on("keydown", function (event) {
                    if (event.keyCode === jQuery.ui.keyCode.TAB &&
                        jQuery(this).autocomplete("instance").menu.active) {
                        event.preventDefault();
                    }
                })
                .autocomplete({
                        source: function (request, response) {
                            const lastEntry = self.extractLastTag(request.term);
                            API.get(apiUrl, {'name': lastEntry}, function(data){
                                response(data);
                            });
                        },
                        search: function () {
                            // custom minLength
                            var term = self.extractLastTag(this.value);
                            if (term.length < self.minChars) {
                                return false;
                            }
                        },
                        focus: function () {
                            // prevent value inserted on focus
                            return false;
                        },
                        select: function (event, ui) {
                            var terms = self.splitTagList(this.value);

                            // remove the current input
                            terms.pop();

                            // check if selected tag is already in list
                            if (!terms.includes(ui.item.value)) {
                                // add the selected item
                                terms.push(ui.item.value);
                            }
                            // add placeholder to get the comma-and-space at the end
                            terms.push("");

                            this.value = terms.join(", ");

                            $(this).trigger('change');

                            return false;
                        }
                    }
                )
            ;
        });
    }

    destroyAutocomplete(selector) {
        jQuery(selector + ' ' + this.selector).each(function(index) {
            const currentField = jQuery(this);
            currentField.autocomplete("destroy");
            currentField.removeData('autocomplete');
        });
    }

}
