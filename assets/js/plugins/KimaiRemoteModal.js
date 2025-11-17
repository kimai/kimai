/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiRemoteModal: load remote content (without forms) into a modal
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
        };

        for (let link of document.querySelectorAll(this._selector)) {
            link.addEventListener('click', this.handle);
        }
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
                    const modalBody = document.createElement('div');
                    modalBody.classList.add('modal-body');
                    modalBody.classList.add('p-0');
                    modalBody.innerHTML = html;

                    this._initElement(modalBody);

                    const modal = this._getModalElement();
                    modal.querySelector('.modal-body').replaceWith(modalBody);
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
