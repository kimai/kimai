/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import TomSelect from 'tom-select';
import KimaiFormTomselectPlugin from "./KimaiFormTomselectPlugin";

/**
 * Supporting auto-complete fields via API.
 */
export default class KimaiAutocomplete extends KimaiFormTomselectPlugin {

    init()
    {
        this.selector = '[data-form-widget="autocomplete"]';
    }

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form) // eslint-disable-line no-unused-vars
    {
        return true;
    }

    loadData(apiUrl, query, callback) {
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');

        API.get(apiUrl, {'name': query}, (data) => {
            let results = [];
            for (let item of data) {
                results.push({text: item.name, value: item.name});
            }
            callback(results);
        }, () => {
            callback();
        });
    }

    activateForm(form)
    {
        [].slice.call(form.querySelectorAll(this.selector)).map((node) => {
            const apiUrl = node.dataset['autocompleteUrl'];
            let minChars = 3;
            if (node.dataset['minimumCharacter'] !== undefined) {
                minChars = parseInt(node.dataset['minimumCharacter']);
            }

            let options = {
                // see https://github.com/orchidjs/tom-select/issues/543#issuecomment-1664342257
                onItemAdd: function(){
                    // remove remaining characters from input after selecting an item
                    this.setTextboxValue('');
                },
                // if there are more than 500, they need to be found by "typing"
                maxOptions: 500,
                // the autocomplete is ONLY used, when the user can create tags
                create: node.dataset['create'] !== undefined,
                onOptionAdd: (value) => {
                    node.dispatchEvent(new CustomEvent('create', {detail: {'value': value}}));
                },
                plugins: ['remove_button'],
                shouldLoad: function(query) {
                    return query.length >= minChars;
                },
                load: (query, callback) => {
                    this.loadData(apiUrl, query, callback);
                },
            };

            let render = {
                // eslint-disable-next-line
                not_loading: (data, escape) => {
                    // no default content
                },
            };

            const rendererType = (node.dataset['renderer'] !== undefined) ? node.dataset['renderer'] : 'default';
            options.render = {...render, ...this.getRenderer(rendererType)};

            new TomSelect(node, options);
        });
    }

    destroyForm(form) {
        [].slice.call(form.querySelectorAll(this.selector)).map((node) => {
            if (node.tomselect) {
                node.tomselect.destroy();
            }
        });
    }

}
