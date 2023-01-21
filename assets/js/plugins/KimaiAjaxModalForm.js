/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAjaxModalForm
 *
 * allows to assign the given selector to any element, which then is used as click-handler:
 * opening a modal with the content from the URL given in the elements 'data-href' or 'href' attribute
 */

import KimaiReducedClickHandler from "./KimaiReducedClickHandler";
import { Modal } from 'bootstrap';

export default class KimaiAjaxModalForm extends KimaiReducedClickHandler {

    constructor(selector) {
        super();
        this._selector = selector;
    }

    getId()
    {
        return 'modal';
    }

    init()
    {
        this._isDirty = false;

        const modalElement = this._getModalElement();
        if (modalElement === null) {
            return;
        }

        modalElement.addEventListener('hide.bs.modal', (event) => {
            if (this._isDirty) {
                if (modalElement.querySelector('.modal-body .remote_modal_is_dirty_warning') === null) {
                    const msg = this.translate('modal.dirty');
                    const temp = document.createElement('div');
                    temp.innerHTML = '<p class="text-danger small remote_modal_is_dirty_warning">' + msg + '</p>';
                    modalElement.querySelector('.modal-body').prepend(temp.firstElementChild);
                }
                event.preventDefault();
                return;
            }
            this._isDirty = false;
            document.dispatchEvent(new Event('modal-hide'));
        });

        modalElement.addEventListener('hidden.bs.modal', () => {
            // kill all references, so GC can kick in
            this.getContainer().getPlugin('form').destroyForm(this._getFormIdentifier());
            modalElement.querySelector('.modal-body').replaceWith('');
        });

        modalElement.addEventListener('show.bs.modal', () => {
            document.dispatchEvent(new Event('modal-show'));
        });

        this.addClickHandler(this._selector, (href) => {
            this.openUrlInModal(href);
        });
    }

    _getModal()
    {
        return Modal.getOrCreateInstance(this._getModalElement())
    }

    /**
     * @param {string} url
     * @param {function(Response)} error the callback to execute if the fetch failed
     */
    openUrlInModal(url, error)
    {
        const headers = new Headers();
        headers.append('X-Requested-With', 'Kimai-Modal');

        this.fetch(url, {
            method: 'GET',
            redirect: 'follow',
            headers: headers
        })
        .then(response => {
            if (!response.ok) {
                window.location = url;
                return;
            }

            return response.text().then(html => {
                this._openFormInModal(html);
            });
        })
        .catch((reason) =>  {
            if (error === undefined || error === null) {
                window.location = url;
            } else {
                error(reason);
            }
        });
    }

    /**
     * Returns the CSS selector for the modal form.
     * 
     * @returns {string}
     * @private
     */
    _getFormIdentifier()
    {
        return '#remote_form_modal .modal-content form';
    }

    /**
     * @returns {HTMLElement|null}
     * @private
     */
    _getModalElement()
    {
        return document.getElementById('remote_form_modal');
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
            node.parentNode.replaceChild(script, node);
        } else {
            for (const child of node.childNodes) {
                this._makeScriptExecutable(child);
            }
        }

        return node;
    }

    _openFormInModal(html)
    {
        const formIdentifier = this._getFormIdentifier();
        let remoteModal = this._getModalElement();
        const newFormHtml = document.createElement('div');
        newFormHtml.innerHTML = html;
        const newModalContent = this._makeScriptExecutable(newFormHtml.querySelector('#form_modal .modal-content'));

        // load new form from given content
        if (newModalContent !== null) {
            // Support changing modal sizes
            let modalDialog = remoteModal.querySelector('.modal-dialog');
            let largeModal = newFormHtml.querySelector('.modal-dialog').classList.contains('modal-lg');

            if (largeModal && !modalDialog.classList.contains('modal-lg')) {
                modalDialog.classList.toggle('modal-lg');
            }

            if (!largeModal && modalDialog.classList.contains('modal-lg')) {
                modalDialog.classList.toggle('modal-lg');
            }

            remoteModal.querySelector('.modal-content').replaceWith(newModalContent);
            [].slice.call(remoteModal.querySelectorAll('[data-bs-dismiss="modal"]')).map((element) => {
                element.addEventListener('click', () => {
                    this._isDirty = false;
                    this._getModal().hide();
                });
            });

            // activate new loaded widgets
            this.getContainer().getPlugin('form').activateForm(formIdentifier);
        }

        // show error flash messages
        let flashMessages = newFormHtml.querySelector('div.alert');
        if (flashMessages !== null) {
            remoteModal.querySelector('.modal-body').prepend(flashMessages);
        }

        // the new form that was loaded via ajax
        const form = document.querySelector(formIdentifier);

        form.addEventListener('change', () => {
            this._isDirty = true;
        });

        // click handler for modal save button, to send forms via ajax
        form.addEventListener('submit', this._getEventHandler());

        this._getModal().show();
    }

    _getEventHandler()
    {
        if (this.eventHandler === undefined) {
            this.eventHandler = (event) => {
                const form = event.target;

                // if the form has a target, we let the normal HTML flow happen
                if (form.target !== undefined && form.target !== '') {
                    return true;
                }

                // otherwise we do some AJAX magic to process the form in the background
                /** @type {HTMLButtonElement} btn */
                const btn = document.querySelector(this._getFormIdentifier() + ' button[type=submit]');
                btn.textContent = btn.textContent + ' …';
                btn.disabled = true;

                const eventName = form.dataset['formEvent'];
                /** @type {KimaiEvent} alert */
                const events = this.getContainer().getPlugin('event');
                /** @type {KimaiAlert} alert */
                const alert = this.getContainer().getPlugin('alert');

                event.preventDefault();
                event.stopPropagation();

                const headers = new Headers();
                headers.append('X-Requested-With', 'Kimai-Modal');
                const options = {headers: headers};

                this.fetchForm(form, options)
                    .then(response => {
                        response.text().then((html) => {
                            /** @type {HTMLDivElement} responseHtml */
                            const responseHtml = document.createElement('div');
                            responseHtml.innerHTML = html;
                            let hasFieldError = false;
                            let hasFormError = false;
                            let hasFlashError = false;

                            // button must be re-enabled anyway
                            btn.textContent = btn.textContent.replace(' …', '');
                            btn.disabled = false;

                            // if the request was successful, there will be no form
                            /** @type {Element} modalContent */
                            const modalContent = responseHtml.querySelector('#form_modal .modal-content');
                            if (modalContent !== null) {
                                hasFieldError = modalContent.querySelector('.is-invalid') !== null;
                                if (!hasFieldError) {
                                    // happens when an error occurs for a "hidden or non-classical" form element e.g. creating team without users
                                    hasFieldError = modalContent.querySelector('.invalid-feedback') !== null;
                                }
                                hasFormError = modalContent.querySelector('ul.list-unstyled li.text-danger') !== null;
                                hasFlashError = responseHtml.querySelector('div.alert-danger') !== null;
                            }

                            if (hasFieldError || hasFormError || hasFlashError) {
                                this._openFormInModal(html);
                            } else {
                                events.trigger(eventName);

                                // try to find form defined message first, but
                                let msg = form.dataset['msgSuccess'];
                                // if that is not available: use a generic fallback message
                                if (msg === null || msg === undefined || msg === '') {
                                    msg = 'action.update.success';
                                }
                                this._isDirty = false;
                                this._getModal().hide();
                                alert.success(msg);
                            }
                        });
                    })
                    .catch(error => {
                        let message = form.dataset['msgError'];
                        if (message === null || message === undefined || message === '') {
                            message = 'action.update.error';
                        }

                        alert.error(message, error.message);

                        // this is useful for changing form fields and retrying to save (and in development to test form changes)
                        setTimeout(() =>{
                            // critical error, allow to re-submit?
                            btn.textContent = btn.textContent.replace(' …', '');
                            btn.disabled = false;
                        }, 1500);
                    });
            };
        }

        return this.eventHandler;
    }

}
