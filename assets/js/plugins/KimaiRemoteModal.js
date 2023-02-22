/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiRecentActivities: responsible to reload the users recent activities
 */

import KimaiPlugin from '../KimaiPlugin';
import { Modal } from 'bootstrap';

export default class KimaiRemoteModal extends KimaiPlugin {

    constructor()
    {
        super();
        this._selector = 'a.remote-modal-load';
    }

    /**
     * @returns {string}
     */
    getId()
    {
        return 'remote-modal';
    }

    init()
    {
        this.handle = (event) => {
            this._showModal(event.currentTarget);
            event.stopPropagation();
            event.preventDefault();
        }

        for (let link of document.querySelectorAll(this._selector)) {
            link.addEventListener('click', this.handle);
        }

        document.addEventListener('kimai.closeRemoteModal', () => { this._hide(); });
    }

    /**
     * @param {HTMLElement} element
     * @private
     */
    _initElement(element)
    {
        for (let link of element.querySelectorAll('a.remote-modal-reload')) {
            link.addEventListener('click', this.handle);
        }
    }

    _hide()
    {
        this._getModal().hide();
    }

    _getModalElement()
    {
        return document.getElementById('remote_modal');
    }

    /**
     * @returns {Modal}
     * @private
     */
    _getModal()
    {
        return Modal.getOrCreateInstance(this._getModalElement());
    }

    /**
     * @param {HTMLLinkElement} element
     * @private
     */
    _showModal(element)
    {
        this.fetch(element.href, {method: 'GET'})
            .then(response => {
                if (!response.ok) {
                    return;
                }

                return response.text().then(html => {
                    const newFormHtml = document.createElement('div');
                    newFormHtml.classList.add('modal-body');
                    newFormHtml.classList.add('p-0');
                    newFormHtml.innerHTML = html;

                    this._initElement(newFormHtml);

                    const modal = this._getModalElement();
                    modal.querySelector('.modal-body').replaceWith(newFormHtml);
                    if (element.dataset['modalTitle'] !== undefined) {
                        modal.querySelector('.modal-title').textContent = element.dataset['modalTitle'];
                    }

                    this._getModal().show();
                });
            })
            .catch((reason) =>  {
                console.log('Failed to load remote modal', reason);
            });
    }
}
