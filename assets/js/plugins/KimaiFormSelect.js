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

    init() {
        // selects the original value inside select2 dropdowns, as the "reset" event (the updated option)
        // is not automatically catched by select2
        jQuery('body').on('reset', 'form', function(event) {
            setTimeout(function() {
                jQuery(event.target).find(this.selector).trigger('change');
            }, 10);
        });

        const self = this;

        // Function to match the name of the parent and not only the names of the children
        // Based on the original matcher function of Select2: https://github.com/select2/select2/blob/5765090318c4d382ae56463cfa25ba8ca7bdd495/src/js/select2/defaults.js#L272
        // More information: https://select2.org/searching | https://github.com/select2/docs/blob/develop/pages/11.searching/docs.md
        this.matcher = function (params, data) {
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

                    let matches = self.matcher(newParams, child);

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
    }

    activateSelectPickerByElement(node, container) {
        let options = {};
        if (container !== undefined) {
            options = {
                dropdownParent: jQuery(container),
            };
        }

        options = {...options, ...{
            language: this.getConfiguration('locale').replace('_', '-'),
            theme: "bootstrap",
            matcher: this.matcher,
            dropdownAutoWidth: true,
            width: "resolve"
        }};

        const element = jQuery(node);

        if (node.dataset['renderer'] !== undefined && node.dataset['renderer'] === 'color') {
            const templateResultFunc = function (state) {
                return jQuery('<span><span style="background-color:'+state.id+'; width: 20px; height: 20px; display: inline-block; margin-right: 10px;">&nbsp;</span>' + state.text + '</span>');
            };

            const colorOptions = {...options, ...{
                templateSelection: templateResultFunc,
                templateResult: templateResultFunc
            }};

            element.select2(colorOptions);
        } else {
            element.select2(options);
        }

        // this is a bugfix for safari, which does render the dropdown only with correct width upon the second opening
        // see https://github.com/select2/select2/issues/4678
        element.on('select2:open', function (ev) {
            if (element.data('performing-reopen') === undefined || element.data('performing-reopen') === null) {
                element.data('performing-reopen', true);
                element.select2('close');
                element.select2('open');
            }
        });
    }

    activateSelectPicker(selector, container) {
        const self = this;
        jQuery(selector + ' ' + this.selector).each(function(i, el) {
            self.activateSelectPickerByElement(el, container);
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
            select.append(this._createOption(emptyOption.text(), ''));
        }

        let emptyOpts = [];
        let options = [];
        let titlePattern = null;
        if (select[0] !== undefined && select[0].dataset !== undefined && select[0].dataset['optionPattern'] !== undefined) {
            titlePattern = select[0].dataset['optionPattern'];
        }
        if (titlePattern === null || titlePattern === '') {
            titlePattern = '{name}';
        }

        for (const [key, value] of Object.entries(data)) {
            if (key === '__empty__') {
                for (const entity of value) {
                    emptyOpts.push(this._createOption(this._getTitleFromPattern(titlePattern, entity), entity.id));
                }
                continue;
            }

            let optGroup = this._createOptgroup(key);
            for (const entity of value) {
                optGroup.appendChild(this._createOption(this._getTitleFromPattern(titlePattern, entity), entity.id));
            }
            options.push(optGroup);
        }

        select.append(options);
        select.append(emptyOpts);

        // if available, re-select the previous selected option (mostly usable for global activities)
        select.val(selectedValue);

        // pre-select an option if it is the only available one
        if (select.val() === '' || select.val() === null) {
            const allOptions = select.find('option');
            const optionLength = allOptions.length;
            let selectOption = '';

            if (optionLength === 1) {
                selectOption = allOptions[0].value;
            } else if (optionLength === 2 && emptyOption.length === 1) {
                selectOption = allOptions[1].value;
            }

            if (selectOption !== '') {
                select.val(selectOption);
            }
        }

        // if we don't trigger the change, the other selects won't reset
        select.trigger('change');

        // if select2 is active, this will tell the select to refresh
        if (select.hasClass('selectpicker')) {
            select.trigger('change.select2');
        }
    }

    /**
     * @param {string} pattern
     * @param {array} entity
     * @private
     */
    _getTitleFromPattern(pattern, entity) {
        const DATE_UTILS = this.getPlugin('date');
        const regexp = new RegExp('{[^}]*?}','g');
        let title = pattern;
        let match = null;

        while ((match = regexp.exec(pattern)) !== null) {
            const field = match[0].substr(1, match[0].length - 2);
            let value = entity[field] === undefined ? null : entity[field];
            if ((field === 'start' || field === 'end')) {
                if (value === null) {
                    value = '?';
                } else {
                    value = DATE_UTILS.getFormattedDate(value);
                }
            }

            title = title.replace(new RegExp('{' + field + '}', 'g'), value ?? '');
        }
        title = title.replace(/- \?-\?/, '');
        title = title.replace(/\r\n|\r|\n/g, ' ');
        title = title.substr(0, 110);

        const chars = '- ';
        let start = 0, end = title.length;

        while (start < end && chars.indexOf(title[start]) >= 0) {
            ++start;
        }

        while (end > start && chars.indexOf(title[end - 1]) >= 0) {
            --end;
        }

        return (start > 0 || end < title.length) ? title.substring(start, end) : title;
    }

    /**
     * @param {string} label
     * @param {string} value
     * @returns {HTMLElement}
     * @private
     */
    _createOption(label, value) {
        let option = document.createElement('option');
        option.innerText = label;
        option.value = value;
        return option;
    }

    /**
     * @param {string} label
     * @returns {HTMLElement}
     * @private
     */
    _createOptgroup(label) {
        let optGroup = document.createElement('optgroup');
        optGroup.label = label;
        return optGroup;
    }
}
