/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiFormPlugin: base class for all none ID plugin that handle forms
 */

import KimaiFormPlugin from './KimaiFormPlugin';

export default class KimaiFormTomselectPlugin extends KimaiFormPlugin {

    /**
     * @param {string} rendererType
     * @return array
     */
    getRenderer(rendererType)
    {
        // default renderer

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
        };

        if (rendererType === 'color') {
            render = {...render, ...{
                option: function(data, escape) {
                    let item = '<div class="list-group-item border-0 p-1 ps-2 text-nowrap">';
                    // if no color is set, do NOT add an empty placeholder
                    if (data.color !== undefined) {
                        item += '<span style="background-color:' + data.color + '" class="color-choice-item me-2">&nbsp;</span>';
                    }
                    item += escape(data.text) + '</div>';
                    return item;
                },
                item: function(data, escape) {
                    let item = '<div class="text-nowrap">';
                    // if no color is set, do NOT add an empty placeholder
                    if (data.color !== undefined) {
                        item += '<span style="background-color:' + data.color + '" class="color-choice-item me-2">&nbsp;</span>';
                    }
                    item += escape(data.text) + '</div>';
                    return item;
                }
            }};
        } else {
            render = {...render, ...{
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

        return render;
    }

}
