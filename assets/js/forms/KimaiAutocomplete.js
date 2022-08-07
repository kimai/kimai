/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import TomSelect from 'tom-select';
import KimaiFormPlugin from "./KimaiFormPlugin";

/**
 * Supporting auto-complete fields via API.
 * Used for timesheet tagging in toolbar and edit dialogs.
 */
export default class KimaiAutocomplete extends KimaiFormPlugin {

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

    activateForm(form)
    {
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');

        [].slice.call(form.querySelectorAll(this.selector)).map((node) => {
            const apiUrl = node.dataset['autocompleteUrl'];
            let minChars = 3;
            if (node.dataset['minimumCharacter'] !== undefined) {
                minChars = parseInt(node.dataset['minimumCharacter']);
            }

            new TomSelect(node, {
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
                    API.get(apiUrl, {'name': query}, (data) => {
                        const results = [].slice.call(data).map((result) => {
                            return {text: result, value: result};
                        });
                        callback(results);
                    }, () => {
                        callback();
                    });
                },
                render: {
                    // eslint-disable-next-line
                    not_loading: (data, escape) => {
                        // no default content
                    },
                    option_create: (data, escape) => {
                        const name = escape(data.input);
                        if (name.length < 3) {
                            return null;
                        }
                        const tpl = this.translate('select.search.create');
                        const tplReplaced = tpl.replace('%input%', '<strong>' + name + '</strong>')
                        return '<div class="create">' + tplReplaced + '</div>';
                    },
                    no_results: (data, escape) => {
                        const tpl = this.translate('select.search.notfound');
                        const tplReplaced = tpl.replace('%input%', '<strong>' + escape(data.input) + '</strong>')
                        return '<div class="no-results">' + tplReplaced + '</div>';
                    },
                },
            });
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
