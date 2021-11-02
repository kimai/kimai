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
import TomSelect from 'tom-select';

export default class KimaiFormSelect extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'form-select';
    }

    init() {
        const self = this;
        // selects the original value inside dropdowns, as the "reset" event (the updated option)
        // is not automatically catched by the JS element
        document.addEventListener('reset', (event) => {
            if (event.target.tagName.toUpperCase() === 'FORM') {
                setTimeout(function() {
                    const fields = event.target.querySelectorAll(self.selector);
                    for (let field of fields) {
                        if (field.tagName.toUpperCase() === 'SELECT') {
                            field.dispatchEvent(new Event('data-reloaded'));
                        }
                    }
                }, 10);
            }
        });
    }

    activateSelectPickerByElement(node, container) {
        let plugins = ['change_listener'];

        const isMultiple = node.multiple !== undefined && node.multiple === true;

        /*
        const isOrdering = false;
        if (isOrdering) {
            plugins.push('caret_position');
            plugins.push('drag_drop');
        }
        */

        if (isMultiple) {
            plugins.push('remove_button');
        }

        let options = {
            lockOptgroupOrder: true,
            allowEmptyOption: true,
            persist: false,
            create: false,
            plugins: plugins,
        };

        if (node.dataset.disableSearch !== undefined) {
            options = {...options, ...{
                controlInput: null,
            }};
        }

        if (node.dataset['renderer'] !== undefined && node.dataset['renderer'] === 'color') {
            options = {...options, ...{
                render: {
                    option: function(data, escape) {
                        return '<div class="list-group-item border-0 p-1 ps-2"><span style="background-color:' + data.value + '; width: 20px; height: 20px; display: inline-block; margin-right: 10px;">&nbsp;</span>' + escape(data.text) + '</div>';
                    },
                    item: function(data, escape) {
                        return '<div><span style="background-color:' + data.value + '; width: 20px; height: 20px; display: inline-block; margin-right: 10px;">&nbsp;</span>' + escape(data.text) + '</div>';
                    }
                }
            }};
        } else {
            options = {...options, ...{
                render: {
                    // the empty entry would collapse and only show as a tiny 5px line if there is no content inside
                    option: function(data, escape) {
                        let text = data.text;
                        if (text === null || text.trim() === '') {
                            text = '&nbsp;';
                        } else {
                            text = escape(text);
                        }
                        return '<div>' + text + '</div>';
                    },
                }
            }};
        }

        const select = new TomSelect(node, options);
        node.addEventListener('data-reloaded', (event) => {
            select.clear(true);
            select.clearOptionGroups();
            select.clearOptions();
            select.sync();
            select.setValue(event.detail);
            select.refreshItems();
            select.refreshOptions(false);
        });

        node.addEventListener('goodbye', () => {
            select.destroy();
        });
    }

    activateSelectPicker(selector, container) {
        const fields = document.querySelectorAll(selector + ' ' + this.selector);
        for (let field of fields) {
            this.activateSelectPickerByElement(field, container);
        }
    }

    destroySelectPicker(selector) {
        const node = document.querySelector(selector);
        node.dispatchEvent(new Event('goodbye'));
    }

    updateOptions(selectIdentifier, data) {
        let emptyOption = null;
        const node = document.querySelector(selectIdentifier);
        const selectedValue = node.value;

        for (let i = 0; i < node.options.length; i++) {
            if (node.options[i].value === '') {
                emptyOption = node.options[i];
            }
        }

        node.options.length = 0;

        if (emptyOption !== null) {
            node.appendChild(this._createOption(emptyOption.text, ''));
        }

        let emptyOpts = [];
        let options = [];

        for (const [key, value] of Object.entries(data)) {
            if (key === '__empty__') {
                for (const entity of value) {
                    emptyOpts.push(this._createOption(entity.name, entity.id));
                }
                continue;
            }

            let optGroup = this._createOptgroup(key);
            for (const entity of value) {
                optGroup.appendChild(this._createOption(entity.name, entity.id));
            }
            options.push(optGroup);
        }

        options.forEach(child => node.appendChild(child));
        emptyOpts.forEach(child => node.appendChild(child));

        // if available, re-select the previous selected option (mostly usable for global activities)
        node.value = selectedValue;
        // this will update the attached javascript component
        node.dispatchEvent(new CustomEvent('data-reloaded', {detail: selectedValue}));
        // if we don't trigger the change, the other selects won't reset
        node.dispatchEvent(new Event('change'));
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
