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
        options = {...options, ...{
            language: this.getContainer().getConfiguration().get('locale'),
            theme: "bootstrap"
        }};
        jQuery(selector + ' ' + this.selector).select2(options);
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

        // if we don't trigger the change, the other selects won't be resetted
        select.trigger('change');

        // if the beta test kimai.theme.select_type is active, this will tell the selects to refresh
        if (select.hasClass('selectpicker')) {
            select.trigger('change.select2');
        }
    }
}
