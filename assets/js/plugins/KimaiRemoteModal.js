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

/**
 * Use like this:
 * <a href="{{ path('your-route') }}" class="remote-modal-load" data-modal-id="remote_modal" data-modal-class="p-0" data-modal-title="Some title" title="Some title">Modal</a>
 * <a href="{{ path('your-route') }}" class="remote-modal-load" data-modal-title="Some title" title="Some title">Modal</a>
 */
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

                let modalSelector = 'remote_modal';
                if (element.dataset['modalId'] !== undefined) {
                    modalSelector = element.dataset['modalId'];
                }
                const modalElement = document.getElementById(modalSelector);
                if (modalElement === null) {
                    console.log('Could not find modal with ID: ' + modalSelector);
                }

                return response.text().then(html => {
                    const modalBody = document.createElement('div');
                    modalBody.classList.add('modal-body');
                    if (element.dataset['modalClass'] !== undefined) {
                        modalBody.classList.add(element.dataset['modalClass']);
                    }
                    modalBody.innerHTML = html;

                    for (let link of modalBody.querySelectorAll('a.remote-modal-reload')) {
                        link.addEventListener('click', this.handle);
                    }

                    modalElement.querySelector('.modal-body').replaceWith(modalBody);
                    if (element.dataset['modalTitle'] !== undefined) {
                        modalElement.querySelector('.modal-title').textContent = element.dataset['modalTitle'];
                    }

                    Modal.getOrCreateInstance(modalElement).show();
                });
            })
            .catch((reason) =>  {
                console.log('Failed to load remote modal', reason);
            });
    }
}
