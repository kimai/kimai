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

            // Always return the parent option including its child, when the name matches the params
            var original = data.text.toUpperCase();
            var term = params.term.toUpperCase();

            // Check if the text contains the term
            if (original.indexOf(term) > -1) {
                return data;
            }

            // Do a recursive check for options with children
            if (data.children && data.children.length > 0) {
                // Clone the data object if there are children
                // This is required as we modify the object to remove any non-matches
                var match = jQuery.extend(true, {}, data);

                // Check each child of the option
                for (var c = data.children.length - 1; c >= 0; c--) {
                    var child = data.children[c];

                    var matches = matcher(params, child);

                    // If there wasn't a match, remove the object in the array
                    if (matches == null) {
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
            language: this.getContainer().getConfiguration().get('locale'),
            theme: "bootstrap",
            matcher: matcher
        }};
        jQuery(selector + ' ' + this.selector).select2(options);
    }

    destroySelectPicker(selector) {
        jQuery(selector + ' ' + this.selector).select2('destroy');
    }

    updateOptions(selectIdentifier, data) {
        let select = jQuery(selectIdentifier);
        let emptyOption = jQuery(selectIdentifier + ' option[value=""]');

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

        // if we don't trigger the change, the other selects won't be resetted
        select.trigger('change');

        // if the beta test kimai.theme.select_type is active, this will tell the selects to refresh
        if (select.hasClass('selectpicker')) {
            select.trigger('change.select2');
        }
    }
}
