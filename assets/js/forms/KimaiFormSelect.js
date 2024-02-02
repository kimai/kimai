/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiFormSelect: enhanced functionality for HTMLSelectElement
 */

import TomSelect from 'tom-select';
import KimaiFormPlugin from "./KimaiFormPlugin";

export default class KimaiFormSelect extends KimaiFormPlugin {

    constructor(selector, apiSelects)
    {
        super();
        this._selector = selector;
        this._apiSelects = apiSelects;
    }

    getId()
    {
        return 'form-select';
    }

    init()
    {
        // selects the original value inside dropdowns, as the "reset" event (the updated option)
        // is not automatically propagated to the JS element
        document.addEventListener('reset', (event) => {
            if (event.target.tagName.toUpperCase() === 'FORM') {
                setTimeout(() => {
                    const fields = event.target.querySelectorAll(this._selector);
                    for (let field of fields) {
                        if (field.tagName.toUpperCase() === 'SELECT') {
                            field.dispatchEvent(new Event('data-reloaded'));
                        }
                    }
                }, 10);
            }
        });
    }

    /**
     * @param {HTMLFormElement} node
     */
    activateSelectPickerByElement(node)
    {
        let plugins = ['change_listener'];

        const isMultiple = node.multiple !== undefined && node.multiple === true;
        const isRequired = node.required !== undefined && node.required === true;

        if (isRequired) {
            plugins.push('no_backspace_delete');
        }

        if (isMultiple) {
            plugins.push('remove_button');
        }

        /*
        const isOrdering = false;
        if (isOrdering) {
            plugins.push('caret_position');
            plugins.push('drag_drop');
        }
        */

        let options = {
            // see https://github.com/orchidjs/tom-select/issues/543#issuecomment-1664342257
            onItemAdd: function(){
                // remove remaining characters from input after selecting an item
                this.setTextboxValue('');
            },
            lockOptgroupOrder: true,
            allowEmptyOption: !isRequired,
            hidePlaceholder: false,
            plugins: plugins,
            // if there are more than X entries, the other ones are hidden and can only be found
            // by typing some characters to trigger the internal option search
            maxOptions: 500,
            sortField:[{field: '$order'}, {field: '$score'}],
        };

        let render = {
            option_create: (data, escape) => {
                const name = escape(data.input);
                if (name.length < 3) {
                    return null;
                }
                const tpl = this.translate('select.search.create');
                const tplReplaced = tpl.replace('%input%', '<strong>' + name + '</strong>');
                return '<div class="create">' + tplReplaced + '</div>';
            },
            no_results: (data, escape) => {
                const tpl = this.translate('select.search.notfound');
                const tplReplaced = tpl.replace('%input%', '<strong>' + escape(data.input) + '</strong>');
                return '<div class="no-results">' + tplReplaced + '</div>';
            },
            onOptionAdd: (value) => {
                node.dispatchEvent(new CustomEvent('create', {detail: {'value': value}}));
            },
        };

        if (node.dataset['create'] !== undefined) {
            options = {...options, ...{
                persist: true,
                create: true,
            }};
        } else {
            options = {...options, ...{
                persist: false,
                create: false,
            }};
        }

        if (node.dataset.disableSearch !== undefined) {
            options = {...options, ...{
                controlInput: null,
            }};
        }

        if (node.dataset['renderer'] !== undefined && node.dataset['renderer'] === 'color') {
            options.render = {...render, ...{
                option: function(data, escape) {
                    let item = '<div class="list-group-item border-0 p-1 ps-2 text-nowrap">';
                    if (data.color !== undefined) {
                        item += '<span style="background-color:' + data.color + '" class="color-choice-item">&nbsp;</span>';
                    } else {
                        item += '<span class="color-choice-item">&nbsp;</span>';
                    }
                    item += escape(data.text) + '</div>';
                    return item;
                },
                item: function(data, escape) {
                    let item = '<div class="text-nowrap">';
                    if (data.color !== undefined) {
                        item += '<span style="background-color:' + data.color + '" class="color-choice-item">&nbsp;</span>';
                    } else {
                        item += '<span class="color-choice-item">&nbsp;</span>';
                    }
                    item += escape(data.text) + '</div>';
                    return item;
                }
            }};
        } else {
            options.render = {...render, ...{
                // the empty entry would collapse and only show as a tiny 5px line if there is no content inside
                option: function(data, escape) {
                    let text = data.text;
                    if (text === null || text.trim() === '') {
                        text = '&nbsp;';
                    } else {
                        text = escape(text);
                    }
                    return '<div>' + text + '</div>';
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

        // support reloading the list upon external event
        if (node.dataset['reload'] !== undefined) {
            node.addEventListener('reload', () => {
                select.disable();
                node.disabled = true;

                /** @type {KimaiAPI} API */
                const API = this.getContainer().getPlugin('api');

                API.get(node.dataset['reload'], {}, (data) => {
                    this._updateSelect(node, data);
                    select.enable();
                    node.disabled = false;
                });

                node.dispatchEvent(new Event('change'));
            });
        }
    }

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form) // eslint-disable-line no-unused-vars
    {
        return true;
    }

    /**
     * @param {HTMLFormElement} form
     */
    activateForm(form)
    {
        [].slice.call(form.querySelectorAll(this._selector)).map((node) => {
            this.activateSelectPickerByElement(node);
        });

        this._activateApiSelects(this._apiSelects);
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        [].slice.call(form.querySelectorAll(this._selector)).map((node) => {
            if (node.tomselect) {
                node.tomselect.destroy();
            }
        });
    }

    /**
     * @param {string|Element} selectIdentifier
     * @param {object} data
     * @private
     */
    _updateOptions(selectIdentifier, data)
    {
        let emptyOption = null;
        let node = null;
        if (selectIdentifier instanceof Element) {
            node = selectIdentifier;
        } else {
            node = document.querySelector(selectIdentifier);
        }
        if (node === null) {
            console.log('Missing select: ' + selectIdentifier);
            return;
        }
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
        /** @type {string|null} titlePattern */
        let titlePattern = null;
        if (node.dataset !== undefined && node.dataset['optionPattern'] !== undefined) {
            titlePattern = node.dataset['optionPattern'];
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

        options.forEach(child => node.appendChild(child));
        emptyOpts.forEach(child => node.appendChild(child));

        // if available, re-select the previous selected option (mostly usable for global activities)
        node.value = selectedValue;

        // pre-select an option if it is the only available one
        if (node.value === '' || node.value === null) {
            const allOptions = node.options;
            const optionLength = allOptions.length;
            let selectOption = '';

            if (optionLength === 1 && node.dataset['autoselect'] === undefined) {
                selectOption = allOptions[0].value;
            } else if (optionLength === 2 && emptyOption !== null) {
                selectOption = allOptions[1].value;
            }

            if (selectOption !== '') {
                node.value = selectOption;
            }
        }

        // this will update the attached javascript component
        node.dispatchEvent(new CustomEvent('data-reloaded', {detail: node.value}));
        // if we don't trigger the change, the other selects won't reset
        node.dispatchEvent(new Event('change'));
    }

    /**
     * @param {string} pattern
     * @param {array} entity
     * @private
     */
    _getTitleFromPattern(pattern, entity)
    {
        const DATE_UTILS = this.getDateUtils();
        const regexp = new RegExp('{[^}]*?}','g');
        let title = pattern;
        let match = null;

        while ((match = regexp.exec(pattern)) !== null) {
            // cutting a string like "{name}" into "name"
            const field = match[0].slice(1, -1);
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
        title = title.substring(0, 110);

        const chars = '- ';
        let start = 0, end = title.length;

        while (start < end && chars.indexOf(title[start]) >= 0) {
            ++start;
        }

        while (end > start && chars.indexOf(title[end - 1]) >= 0) {
            --end;
        }

        let result = (start > 0 || end < title.length) ? title.substring(start, end) : title;

        if (result === '' && entity['name'] !== undefined) {
            return entity['name'];
        }

        return result;
    }

    /**
     * @param {HTMLSelectElement} select
     * @param {string} label
     * @param {string} value
     * @param {object} dataset
     */
    addOption(select, label, value, dataset)
    {
        const option = this._createOption(label, value);
        for (const key in dataset) {
            option.dataset[key] = dataset[key];
        }

        select.options.add(option);
        if (select.tomselect !== undefined) {
            select.tomselect.sync();
        }
    }

    /**
     *
     * @param {HTMLSelectElement} select
     * @param {HTMLOptionElement} option
     */
    removeOption(select, option)
    {
        option.remove();
        if (select.tomselect !== undefined) {
            select.tomselect.removeOption(option.value, true);
            select.tomselect.clear(true);
        }
    }

    /**
     * @param {string} label
     * @param {string} value
     * @returns {HTMLElement}
     * @private
     */
    _createOption(label, value)
    {
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
    _createOptgroup(label)
    {
        let optGroup = document.createElement('optgroup');
        optGroup.label = label;
        return optGroup;
    }

    /**
     * @param {string} selector
     * @private
     */
    _activateApiSelects(selector)
    {
        if (this._eventHandlerApiSelects === undefined) {
            this._eventHandlerApiSelects = (event) => {
                if (event.target === null || !event.target.matches(selector)) {
                    return;
                }

                const apiSelect = event.target;
                const targetSelectId = '#' + apiSelect.dataset['relatedSelect'];
                /** @type {HTMLSelectElement} targetSelect */
                const targetSelect = document.getElementById(apiSelect.dataset['relatedSelect']);

                // if the related target select does not exist, we do not need to load the related data
                if (targetSelect === null || targetSelect.dataset['reloading'] === '1') {
                    return;
                }
                targetSelect.dataset['reloading'] = '1';

                if (targetSelect.tomselect !== undefined) {
                    targetSelect.tomselect.disable();
                }
                targetSelect.disabled = true;

                let formPrefix = apiSelect.dataset['formPrefix'];
                if (formPrefix === undefined || formPrefix === null) {
                    formPrefix = '';
                } else if (formPrefix.length > 0) {
                    formPrefix += '_';
                }

                let newApiUrl = this._buildUrlWithFormFields(apiSelect.dataset['apiUrl'], formPrefix);

                const selectValue = apiSelect.value;

                // Problem: select a project with activities and then select a customer that has no project
                // results in a wrong URL, it triggers "activities?project=" instead of using the "emptyUrl"
                if (selectValue === undefined || selectValue === null || selectValue === '' || (Array.isArray(selectValue) && selectValue.length === 0)) {
                    if (apiSelect.dataset['emptyUrl'] === undefined) {
                        this._updateSelect(targetSelectId, {});
                        targetSelect.dataset['reloading'] = '0';
                        return;
                    }
                    newApiUrl = this._buildUrlWithFormFields(apiSelect.dataset['emptyUrl'], formPrefix);
                }

                /** @type {KimaiAPI} API */
                const API = this.getContainer().getPlugin('api');

                API.get(newApiUrl, {}, (data) => {
                    this._updateSelect(targetSelectId, data);
                    if (targetSelect.tomselect !== undefined) {
                        targetSelect.tomselect.enable();
                    }
                    targetSelect.dataset['reloading'] = '0';
                    targetSelect.disabled = false;
                });
            }

            document.addEventListener('change', this._eventHandlerApiSelects);
        }
    }

    /**
     * @param {string} apiUrl
     * @param {string} formPrefix
     * @return {string}
     * @private
     */
    _buildUrlWithFormFields(apiUrl, formPrefix)
    {
        let newApiUrl = apiUrl;

        apiUrl.split('?')[1].split('&').forEach(item => {
            const [key, value] = item.split('='); // eslint-disable-line no-unused-vars
            const decoded = decodeURIComponent(value);
            const test = decoded.match(/%(.*)%/);
            if (test !== null) {
                const originalFieldName = test[1];
                const targetFieldName = (formPrefix + originalFieldName).replace(/\[/, '').replace(/]/, '');
                const targetField = document.getElementById(targetFieldName);
                let newValue = '';
                if (targetField === null) {
                    // happens for example:
                    // - in duration only mode, when the end field is not found
                    // console.log('ERROR: Cannot find field with name "' + test[1] + '" by selector: #' + formPrefix + test[1]);
                } else {
                    if (targetField.value !== null) {
                        newValue = targetField.value;
                        if (targetField.tagName === 'SELECT' && targetField.multiple) {
                            newValue = [...targetField.selectedOptions].map(o => o.value);
                        } else if (newValue !== '') {
                            if (targetField.type === 'date') {
                                const timeId = targetField.id.replace('_date', '_time')
                                const timeElement = document.getElementById(timeId);
                                const time = timeElement === null ? '12:00:00' : timeElement.value;
                                // using 12:00 as fallback, because timezone handling might change the date if we use 00:00
                                const newDate = this.getDateUtils().fromHtml5Input(newValue, time);
                                newValue = this.getDateUtils().formatForAPI(newDate, false);
                            } else if (targetField.type === 'text' && targetField.name.includes('date')) {
                                const timeId = targetField.id.replace('_date', '_time')
                                const timeElement = document.getElementById(timeId);
                                // using 12:00 as fallback, because timezone handling might change the date if we use 00:00
                                let time = '12:00:00';
                                let timeFormat = 'HH:mm';
                                if (timeElement !== null) {
                                    time = timeElement.value;
                                    timeFormat = timeElement.dataset['format'];
                                }
                                const newDate = this.getDateUtils().fromFormat(newValue.trim() + ' ' + time.trim(), targetField.dataset['format'] + ' ' + timeFormat);
                                newValue = this.getDateUtils().formatForAPI(newDate, false);
                            } else if (targetField.dataset['format'] !== undefined) {
                                // find out when this else branch is triggered and document!

                                if (this.getDateUtils().isValidDateTime(newValue, targetField.dataset['format'])) {
                                    newValue = this.getDateUtils().format(targetField.dataset['format'], newValue);
                                }
                            }
                        } else {
                            // happens for example:
                            // - when the end date is not set on a timesheet record and the project list is loaded (as the URL contains the %end% replacer)
                            // console.log('Empty value found for field with name "' + test[1] + '" by selector: #' + formPrefix + test[1]);
                        }
                    } else {
                        // happens for example:
                        // - when a customer without projects is selected
                        // console.log('ERROR: Empty field with name "' + test[1] + '" by selector: #' + formPrefix + test[1]);
                    }
                }


                if (Array.isArray(newValue)) {
                    let urlParams = [];
                    for (let tmpValue of newValue) {
                        if (tmpValue === null) {
                            tmpValue = '';
                        }
                        urlParams.push(originalFieldName + '=' + tmpValue);
                    }
                    newApiUrl = newApiUrl.replace(item, urlParams.join('&'));
                } else {
                    if (newValue === null) {
                        newValue = '';
                    }
                    newApiUrl = newApiUrl.replace(value, newValue);
                }
            }
        });

        return newApiUrl;
    }

    /**
     * @param {string|Element} select
     * @param {object} data
     * @private
     */
    _updateSelect(select, data)
    {
        const options = {};
        for (const apiData of data) {
            let title = '__empty__';
            if (apiData['parentTitle'] !== undefined && apiData['parentTitle'] !== null) {
                title = apiData['parentTitle'];
            }
            if (options[title] === undefined) {
                options[title] = [];
            }
            options[title].push(apiData);
        }

        const ordered = {};
        Object.keys(options).sort().forEach(function(key) {
            ordered[key] = options[key];
        });

        this._updateOptions(select, ordered);
    }
}
