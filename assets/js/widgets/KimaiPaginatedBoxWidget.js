/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiPaginatedBoxWidget: handles box widgets that have a pagination
 */

import jQuery from "jquery";

export default class KimaiPaginatedBoxWidget {

    constructor(boxId) {
        this.selector = boxId;
        const widget = document.querySelector(this.selector);
        this.href = widget.dataset['href'];
        const self = this;

        if (widget.dataset['reload'] !== undefined) {
            this.events = widget.dataset['reload'].split(' ');
            const reloadPage = function (event) {
                let url = null;
                if (document.querySelector(self.selector).dataset['reloadHref'] !== undefined) {
                    url = document.querySelector(self.selector).dataset['reloadHref'];
                } else {
                    url = jQuery(self.selector + ' ul.pagination li.active a').attr('href');
                }
                self.loadPage(url);
            };

            for (const eventName of this.events) {
                document.addEventListener(eventName, reloadPage);
            }
        }

        jQuery('body').on('click', this.selector + ' a.pagination-link', function (event) {
            event.preventDefault();
            self.loadPage(jQuery(event.currentTarget).attr('href'));
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
        const hideOverlay = function() {
            document.dispatchEvent(new Event('kimai.reloadedContent'));
        }

        jQuery.ajax({
            url: url,
            data: {},
            success: function (response) {
                const html = jQuery(response);
                // previously the parts .card-header .card-body .card-title .card-footer were replaced
                // but the layout allows eg. ".list-group .list-group-flush" instead of .card-body
                // so we directly replace the entire HTML
                jQuery(selector).replaceWith(html);
                jQuery(selector + ' [data-toggle="tooltip"]').tooltip();
                hideOverlay();
            },
            dataType: 'html',
            error: function(jqXHR, textStatus, errorThrown) {
                // this is not yet a plugin, so the alert is not available here
                // self.getPlugin('alert').error('Failed loading selected page');
                hideOverlay();
            }
        });        
    }
}
