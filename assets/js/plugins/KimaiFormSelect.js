/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiFormSelect: enhanced functionality for HTML select's
 */

import KimaiPlugin from "../KimaiPlugin";
import jQuery from "jquery";

export default class KimaiFormSelect extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'form-select';
    }

    activateSelectPicker(selector, container) {
        const elementSelector = this.selector;
        let options = {};
        if (container !== undefined) {
            options = {
                dropdownParent: $(container),
            };
        }

        // Function to match the name of the parent and not only the names of the children
        // Based on the original matcher function of Select2: https://github.com/select2/select2/blob/5765090318c4d382ae56463cfa25ba8ca7bdd495/src/js/select2/defaults.js#L272
        // More information: https://select2.org/searching | https://github.com/select2/docs/blob/develop/pages/11.searching/docs.md
        function matcher(params, data) {
            // Always return the object if there is nothing to compare
            if (jQuery.trim(params.term) === '') {
                return data;
            }

            // Check whether options has children
            let hasChildren = data.children && data.children.length > 0;

            // Split search param by space to search for all terms and convert all to uppercase
            let terms = params.term.toUpperCase().split(' ');
            let original = data.text.toUpperCase();

            // Always return the parent option including its children, when the name matches one of the params
            // Check if the text contains all or at least one of the terms
            let foundAll = true;
            let foundOne = false;
            let missingTerms = [];
            terms.forEach(function(item, index) {
                if (original.indexOf(item) > -1) {
                    foundOne = true;
                } else {
                    foundAll = false;
                    missingTerms.push(item);
                }
            });

            // If the option element contains all terms, return it
            if (foundAll) {
                return data;
            }

            // Do a recursive check for options with children
            if (hasChildren) {
                // If the parent already contains one or more search terms, proceed only with the missing ones
                // First: Clone the original params object...
                let newParams = jQuery.extend(true, {}, params);
                if (foundOne) {
                    newParams.term = missingTerms.join(' ');
                } else {
                    newParams.term = params.term;
                }

                // Clone the data object if there are children
                // This is required as we modify the object to remove any non-matches
                let match = jQuery.extend(true, {}, data);

                // Check each child of the option
                for (let c = data.children.length - 1; c >= 0; c--) {
                    let child = data.children[c];

                    let matches = matcher(newParams, child);

                    // If there wasn't a match, remove the object in the array
                    if (matches === null) {
                        match.children.splice(c, 1);
                    }
                }

                // If any children matched, return the new object
                if (match.children.length > 0) {
                    return match;
                }
            }

            // If the option or its children do not contain the term, don't return anything
            return null;
        }

        options = {...options, ...{
            language: this.getContainer().getConfiguration().get('locale').replace('_', '-'),
            theme: "bootstrap",
            matcher: matcher
        }};
        jQuery(selector + ' ' + elementSelector).select2(options);

        jQuery('body').on('reset', 'form', function(event){
            setTimeout(function() {
                jQuery(event.target).find(elementSelector).trigger('change');
            }, 10);
        });
    }

    destroySelectPicker(selector) {
        jQuery(selector + ' ' + this.selector).select2('destroy');
    }

    updateOptions(selectIdentifier, data) {
        let select = jQuery(selectIdentifier);
        let emptyOption = jQuery(selectIdentifier + ' option[value=""]');
        const selectedValue = select.val();

        select.find('option').remove().end().find('optgroup').remove().end();

        if (emptyOption.length !== 0) {
            select.append('<option value="">' + emptyOption.text() + '</option>');
        }

        let htmlOptions = '';
        let emptyOptions = '';

        for (const [key, value] of Object.entries(data)) {
            if (key === '__empty__') {
                for (const entity of value) {
                    emptyOptions +=  '<option value="' + entity.id + '">' + entity.name + '</option>';
                }
                continue;
            }

            htmlOptions += '<optgroup label="' + key + '">';
            for (const entity of value) {
                htmlOptions +=  '<option value="' + entity.id + '">' + entity.name + '</option>';
            }
            htmlOptions += '</optgroup>';
        }

        select.append(htmlOptions);
        select.append(emptyOptions);

        // if available, re-select the previous selected option (mostly usable for global activities)
        select.val(selectedValue);

        // if we don't trigger the change, the other selects won't reset
        select.trigger('change');

        // if select2 is active, this will tell the select to refresh
        if (select.hasClass('selectpicker')) {
            select.trigger('change.select2');
        }
    }
}
