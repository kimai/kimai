/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import KimaiPlugin from "../KimaiPlugin";
import TomSelect from 'tom-select';

/**
 * Supporting auto-complete fields via API.
 * Used for timesheet tagging in toolbar and edit dialogs.
 */
export default class KimaiAutocomplete extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'autocomplete';
    }

    activateAutocomplete(selector) {
        /** @type {KimaiAPI} API */
        const API = this.getContainer().getPlugin('api');

        [].slice.call(document.querySelectorAll(selector + ' ' + this.selector)).map((node) => {
            const apiUrl = node.dataset['autocompleteUrl'];
            let minChars = 3;
            if (node.dataset['minimumCharacter'] !== undefined) {
                minChars = parseInt(node.dataset['minimumCharacter']);
            }

            new TomSelect(node, {
                // if there are more than 500, they need to be found by "tipping"
                maxOptions: 500,
                // the autocomplete is ONLY used, when the user can create tags
                create: node.dataset['create'] !== undefined && node.dataset['create'] === 'true',
                plugins: ['remove_button'],
                shouldLoad: function(query) {
                    return query.length >= minChars;
                },
                load: function(query, callback) {
                    API.get(apiUrl, {'name': query}, (data) => {
                        const results = [].slice.call(data).map((result) => {
                            return {text: result, value: result};
                        });
                        callback(results);
                    }, function() {
                        callback();
                    });
                },
                render: {
                    not_loading: function (data, escape) {
                        // no default content
                    },
                    option_create: function (data, escape) {
                        const tpl = node.dataset['transAddResult'] ?? 'Add %input% &hellip;';
                        const tplReplaced = tpl.replace('%input%', '<strong>' + escape(data.input) + '</strong>')
                        return '<div class="create">' + tplReplaced + '</div>';
                    },
                    no_results: function (data, escape) {
                        const tpl = node.dataset['transNoResult'] ?? 'No results found for "%input%"';
                        const tplReplaced = tpl.replace('%input%', '<strong>' + escape(data.input) + '</strong>')
                        return '<div class="no-results">' + tplReplaced + '</div>';
                    },
                },
            });
        });
    }

    destroyAutocomplete(selector) {
        [].slice.call(document.querySelectorAll(selector + ' ' + this.selector)).map((node) => {
            if (node.tomselect) {
                node.tomselect.destroy();
            }
        });
    }

}
