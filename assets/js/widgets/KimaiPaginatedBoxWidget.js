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
        this.overlay = jQuery('<div class="overlay"><div class="fas fa-sync fa-spin"></div></div>');
        this.widget = jQuery(this.selector);
        this.href = this.widget.data('href');
        this.events = this.widget.data('reload').split(' ');

        const self = this;

        const reloadPage = function (event) {
            const page = jQuery(self.selector + ' .box-tools').data('page');
            const url = self._buildUrl(page);
            self.loadPage(url);
        };

        for (const eventName of this.events) {
            document.addEventListener(eventName, reloadPage);
        }

        this.widget.on('click', '.box-tools a', function (event) {
            event.preventDefault();
            self.loadPage(jQuery(event.currentTarget).attr('href'));
        });
    }
    
    static create(elementId) {
        return new KimaiPaginatedBoxWidget(elementId);
    }
    
    _showOverlay() {
        this.widget.append(this.overlay);
    }
    
    _hideOverlay() {
        jQuery(this.overlay).remove();
    }
    
    loadPage(url) {
        const self = this;
        const selector = this.selector;
        
        self._showOverlay();

        jQuery.ajax({
            url: url,
            data: {},
            success: function (response) {
                const html = jQuery(response);
                jQuery(selector + ' .box-tools').replaceWith(html.find('.box-tools'));
                jQuery(selector + ' .box-body').replaceWith(html.find('.box-body'));
                jQuery(selector + ' .box-title').replaceWith(html.find('.box-title'));
                if (jQuery(selector + ' .box-footer').length > 0) {
                    jQuery(selector + ' .box-footer').replaceWith(html.find('.box-footer'));
                }
                jQuery(selector + ' .box-body [data-toggle="tooltip"]').tooltip();
                self.widget.removeData('error-retry');
                self._hideOverlay();
            },
            dataType: 'html',
            error: function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.status !== undefined && jqXHR.status === 500) {
                    if (self.widget.data('error-retry') !== undefined) {
                        // TODO show error message ?
                        return;
                    }
                    const page = jQuery(selector + ' .box-tools').data('page');
                    if (page > 1) {
                        self.widget.data('error-retry', 1);
                        var url = self._buildUrl(page - 1);
                        self.loadPage(url);
                    }
                }
                self._hideOverlay();
            }
        });        
    }
    
    _buildUrl(page) {
        return this.href.replace('1', page);
    }
    
}
