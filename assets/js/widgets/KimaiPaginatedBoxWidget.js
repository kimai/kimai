/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiPaginatedBoxWidget: handles box widgets that have a pagination
 */

import KimaiContextMenu from "./KimaiContextMenu";

export default class KimaiPaginatedBoxWidget {

    constructor(boxId) {
        this.selector = boxId;
        const widget = document.querySelector(this.selector);
        this.href = widget.dataset['href'];

        if (widget.dataset['reload'] !== undefined) {
            this.events = widget.dataset['reload'].split(' ');
            const reloadPage = () => {
                let url = null;
                if (document.querySelector(this.selector).dataset['reloadHref'] !== undefined) {
                    url = document.querySelector(this.selector).dataset['reloadHref'];
                } else {
                    url = document.querySelector(this.selector + ' ul.pagination li.active a').href;
                }
                this.loadPage(url);
            };

            for (const eventName of this.events) {
                document.addEventListener(eventName, reloadPage);
            }
        }

        document.body.addEventListener('click', (event) => {
            let link = event.target;
            // could be an icon
            if (!link.matches(this.selector + ' a.pagination-link')) {
                link = link.parentNode;
            }
            if (link.matches(this.selector + ' a.pagination-link')) {
                event.preventDefault();
                this.loadPage(link.href);
            }
        });
    }
    
    static create(elementId) {
        return new KimaiPaginatedBoxWidget(elementId);
    }
    
    loadPage(url) {
        const selector = this.selector;

        // this event will render a spinning loader
        document.dispatchEvent(new CustomEvent('kimai.reloadContent', {detail: this.selector}));

        // and this event will hide it afterwards
        const hideOverlay = () => {
            document.dispatchEvent(new Event('kimai.reloadedContent'));
        }

        window.kimai.getPlugin('fetch').fetch(url)
            .then(response => {
                response.text().then((text) => {
                    const temp = document.createElement('div');
                    temp.innerHTML = text;
                    // previously the parts .card-header .card-body .card-title .card-footer were replaced
                    // but the layout allows eg. ".list-group .list-group-flush" instead of .card-body
                    // so we directly replace the entire HTML
                    // the HTML needs to be parsed for script tags, which can be included (e.g. paginated chart widget)
                    document.querySelector(selector).replaceWith(this._makeScriptExecutable(temp.firstElementChild));
                    KimaiContextMenu.createForDataTable(selector + ' table.dataTable');
                    hideOverlay();
                });
            })
            .catch(() => {
                // this is not yet a plugin, so the alert is not available here
                window.kimai.getPlugin('alert').error('Failed loading selected page');
                hideOverlay();
            });
    }

    /**
     * @param {Element|ChildNode} node
     * @returns {Element}
     * @private
     */
    _makeScriptExecutable(node) {
        if (node.tagName !== undefined && node.tagName === 'SCRIPT') {
            const script  = document.createElement('script');
            script.text = node.innerHTML;
            node.parentNode.replaceChild(script, node );
        } else {
            for (const child of node.childNodes) {
                this._makeScriptExecutable(child);
            }
        }

        return node;
    }
}
